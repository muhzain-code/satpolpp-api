<?php

namespace App\Services\ManajemenLaporan;

use Illuminate\Support\Facades\DB;
use App\Exceptions\CustomException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Services\OptimizePhotoService;
use Illuminate\Support\Facades\Storage;
use App\Models\ManajemenLaporan\LaporanHarian;
use App\Models\ManajemenLaporan\LaporanLampiran;

class LaporanHarianService
{

    protected OptimizePhotoService $optimizeService;

    public function __construct(OptimizePhotoService $optimizeService)
    {
        $this->optimizeService = $optimizeService;
    }

    public function getAll($perPage, $currentPage, $request): array
    {
        $user = Auth::user();

        $user->load('anggota');

        $query = LaporanHarian::with(['anggota.unit', 'kategoriPelanggaran', 'validator', 'regulasi']);

        // 2. Terapkan Filter Berdasarkan Role (Scope Data)
        if ($user->hasRole('super_admin')) {
            // Super admin melihat semua
        } else if ($user->hasRole('komandan_regu')) {
            if (!$user->anggota) {
                throw new CustomException('Akun Komandan tidak terhubung dengan data anggota/unit.', 403);
            }
            $unitIdKomandan = $user->anggota->unit_id;

            // Filter: Laporan dari anggota yang satu unit dengan komandan
            $query->whereHas('anggota', function ($q) use ($unitIdKomandan) {
                $q->where('unit_id', $unitIdKomandan);
            });
        } else if ($user->hasRole('anggota_regu')) {
            if (!$user->anggota) {
                throw new CustomException('Akun Anggota tidak terhubung dengan data profil.', 403);
            }
            // Filter: Hanya milik sendiri
            $query->where('anggota_id', $user->anggota->id);
        } else {
            throw new CustomException('Akses ditolak. Role tidak dikenali.', 403);
        }

        // 3. Terapkan Filter Request
        if ($request->has('unit_id') && $request->unit_id != null) {
            $query->whereHas('anggota', function ($q) use ($request) {
                $q->where('unit_id', $request->unit_id);
            });
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59'
            ]);
        }

        if ($request->has('jenis') && $request->jenis != null) {
            $query->where('jenis', $request->jenis);
        }

        if ($request->has('severity') && $request->severity != null) {
            $query->where('severity', $request->severity);
        }

        if ($request->has('status_validasi') && $request->status_validasi != null) {
            $query->where('status_validasi', $request->status_validasi);
        }

        if ($request->has('telah_dieskalasi') && $request->telah_dieskalasi !== null) {
            $val = filter_var($request->telah_dieskalasi, FILTER_VALIDATE_BOOLEAN);
            $query->where('telah_dieskalasi', $val);
        }

        // 4. Eksekusi Pagination
        $laporan = $query->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $currentPage);

        // 5. Transform Data (Mapping ke JSON Response)
        $laporan->getCollection()->transform(function ($item) {
            return [
                'id'                => $item->id,
                'tanggal'           => $item->created_at->format('d-m-Y H:i'),

                // Info Personil
                'anggota_nama'      => $item->anggota->nama ?? '-',
                'unit_nama'         => $item->anggota->unit->nama ?? '-',

                // Info Laporan
                'jenis'             => $item->jenis, // aman/insiden
                'severity'          => $item->severity ?? null, // rendah/sedang/tinggi
                'catatan'           => $item->catatan ?? null, // Ini pengganti 'lokasi' teks manual

                // Koordinat (Penting untuk Peta)
                'lat'               => $item->lat,
                'lng'               => $item->lng,

                // Info Pelanggaran (Jika ada)
                'kategori'          => $item->kategoriPelanggaran->nama ?? '-',
                'regulasi'          => $item->regulasi->judul ?? '-', // Jika user melanggar regulasi tertentu

                // Status & Validasi
                'status_validasi'   => $item->status_validasi,
                'divalidasi_oleh'   => $item->validator->name ?? '-', // Ambil nama User validator
                'eskalasi'          => $item->telah_dieskalasi ? 'Ya' : 'Tidak',
            ];
        });

        return [
            'message' => 'Data laporan berhasil ditampilkan',
            'data' => [
                'current_page' => $laporan->currentPage(),
                'per_page'     => $laporan->perPage(),
                'total'        => $laporan->total(),
                'last_page'    => $laporan->lastPage(),
                'items'        => $laporan->items()
            ]
        ];
    }

    public function store(array $data): array
    {
        DB::beginTransaction();

        try {
            $user = Auth::user();

            if (!$user) {
                throw new CustomException('Anda belum login.');
            }

            $anggotaId = null;
            $isSuperAdmin = $user->hasRole('superadmin');

            if ($isSuperAdmin) {
                if (empty($data['anggota_id'])) {
                    throw new CustomException('Superadmin wajib memilih anggota.');
                }
                $anggotaId = $data['anggota_id'];
            } else {
                $anggota = $user->anggota;
                if (!$anggota || $anggota->status !== 'aktif') {
                    throw new CustomException('Akun anggota tidak valid/aktif.');
                }
                $anggotaId = $anggota->id;
            }

            $statusValidasi = 'menunggu';
            $divalidasiOleh = null;

            if ($isSuperAdmin) {
                $statusInput = $data['status_validasi'] ?? 'disetujui';

                $statusValidasi = $statusInput;

                if (in_array($statusValidasi, ['disetujui', 'ditolak'])) {
                    $divalidasiOleh = $user->id;
                }
            } else {
                $statusValidasi = 'menunggu';
            }
            $kategoriId = null;
            $regulasiId = null;
            $severity   = null;

            if (isset($data['jenis']) && $data['jenis'] === 'insiden') {
                $kategoriId = $data['kategori_pelanggaran_id'] ?? null;
                $regulasiId = $data['regulasi_indikatif_id'] ?? null;
                $severity   = $data['severity'] ?? null;

                if (!$severity) throw new CustomException('Severity wajib diisi.');
            }

            $laporan = LaporanHarian::create([
                'anggota_id'              => $anggotaId,
                'jenis'                   => $data['jenis'] ?? 'aman',
                'catatan'                 => $data['catatan'] ?? null,
                'lat'                     => $data['lat'] ?? null,
                'lng'                     => $data['lng'] ?? null,

                'kategori_pelanggaran_id' => $kategoriId,
                'regulasi_indikatif_id'   => $regulasiId,
                'severity'                => $severity,

                'status_validasi'         => $statusValidasi,
                'divalidasi_oleh'         => $divalidasiOleh,

                'created_by'              => $user->id,
            ]);

            if (!empty($data['lampiran']) && is_array($data['lampiran'])) {
                foreach ($data['lampiran'] as $file) {
                    if ($file instanceof \Illuminate\Http\UploadedFile && $file->isValid()) {
                        $path = $this->optimizeService->optimizeImage($file, 'laporan_harian');
                        LaporanLampiran::create([
                            'laporan_id' => $laporan->id,
                            'path_file'  => $path,
                            'nama_file'  => $file->getClientOriginalName(),
                            'jenis'      => str_contains($file->getMimeType(), 'video') ? 'video' : 'foto',
                        ]);
                    }
                }
            }

            DB::commit();
            return [
                'message' => 'Laporan harian berhasil ditambahkan',
                'data'    => $laporan->load('lampiran'),
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Gagal store laporan', ['msg' => $e->getMessage()]);
            if ($e instanceof CustomException) throw $e;
            throw new CustomException('Gagal menambah laporan: ' . $e->getMessage(), 422);
        }
    }

    public function getById($id): array
    {
        $user = Auth::user();

        // Load relasi anggota agar kita bisa cek unit_id nya
        $laporan = LaporanHarian::with(['lampiran', 'anggota'])->find($id);

        if (!$laporan) {
            throw new CustomException('Laporan harian tidak ditemukan', 404);
        }


        if ($user->hasRole('komandan_regu')) {
            if (!$user->anggota) {
                throw new CustomException('Akun Komandan tidak valid (tidak terhubung ke data anggota).', 403);
            }
            if ($user->anggota->unit_id !== $laporan->anggota->unit_id) {
                throw new CustomException('Akses ditolak. Anda hanya bisa melihat laporan anggota unit Anda.', 403);
            }
        }

        if ($user->hasRole('anggota_regu')) {
            if (!$user->anggota) {
                throw new CustomException('Akun Anggota tidak valid.', 403);
            }
            if ($laporan->anggota_id !== $user->anggota->id) {
                throw new CustomException('Akses ditolak. Anda hanya bisa melihat laporan Anda sendiri.', 403);
            }
        }

        // =================================================================

        $laporan->lampiran->transform(function ($item) {
            $item->path_file = url(Storage::url($item->path_file));
            return $item;
        });

        return [
            'message' => 'Data berhasil ditampilkan',
            'data'    => $laporan
        ];
    }

    public function update($id, array $data): array
    {
        $user = Auth::user();

        if (!$user->hasRole('superadmin')) {
            throw new CustomException('Akses ditolak. Hanya Superadmin yang diizinkan mengubah laporan.', 403);
        }

        DB::beginTransaction();
        try {
            $laporan = LaporanHarian::with(['lampiran', 'anggota'])->find($id);

            if (!$laporan) {
                throw new CustomException('Data laporan harian tidak ditemukan', 404);
            }

            if (!empty($data['lampiran'])) {
                foreach ($laporan->lampiran as $lampiran) {
                    if (Storage::disk('public')->exists($lampiran->path_file)) {
                        Storage::disk('public')->delete($lampiran->path_file);
                    }
                    $lampiran->delete();
                }
            }

            $updateData = [
                'jenis'                   => $data['jenis'] ?? $laporan->jenis,
                'catatan'                 => $data['catatan'] ?? $laporan->catatan,
                'lat'                     => $data['lat'] ?? $laporan->lat,
                'lng'                     => $data['lng'] ?? $laporan->lng,
                'kategori_pelanggaran_id' => $data['kategori_pelanggaran_id'] ?? $laporan->kategori_pelanggaran_id,
                'regulasi_indikatif_id'   => $data['regulasi_indikatif_id'] ?? $laporan->regulasi_indikatif_id,
                'severity'                => $data['severity'] ?? $laporan->severity,
                'updated_at'              => now(),
            ];

            if (isset($data['status_validasi'])) {
                $newStatus = $data['status_validasi'];

                $updateData['status_validasi'] = $newStatus;

                if (in_array($newStatus, ['disetujui', 'ditolak'])) {
                    $updateData['divalidasi_oleh'] = $user->id;
                } elseif ($newStatus === 'menunggu') {
                    $updateData['divalidasi_oleh'] = null;
                }
            }

            $laporan->update($updateData);

            if (!empty($data['lampiran'])) {
                foreach ($data['lampiran'] as $file) {
                    if ($file->isValid()) {
                        $path = $this->optimizeService->optimizeImage($file, 'laporan_harian');
                        LaporanLampiran::create([
                            'laporan_id' => $laporan->id,
                            'path_file'  => $path,
                            'nama_file'  => $file->getClientOriginalName(),
                            'jenis'      => str_contains($file->getMimeType(), 'video') ? 'video' : 'foto'
                        ]);
                    }
                }
            }
            DB::commit();

            return [
                'message' => 'Data laporan harian berhasil diperbarui',
                'data'    => $laporan->fresh()->load('lampiran')
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Gagal memperbarui laporan harian', [
                'error' => $e->getMessage(),
                'user_id' => $user->id
            ]);

            if ($e instanceof CustomException) {
                throw $e;
            }

            throw new CustomException('Gagal memperbarui laporan harian: ' . $e->getMessage(), 500);
        }
    }

    public function delete($id): array
    {
        $user = Auth::user();

        // 1. BLOKIR AKSES ANGGOTA
        if ($user->hasRole('anggota_regu')) {
            throw new CustomException('Akses ditolak. Anggota tidak diizinkan menghapus laporan.', 403);
        }

        $laporan = LaporanHarian::with(['lampiran', 'anggota'])->find($id);

        if (!$laporan) {
            throw new CustomException('Data laporan harian tidak ditemukan', 404);
        }

        // 2. CEK AKSES KOMANDAN
        if ($user->hasRole('komandan_regu')) {
            if (!$user->anggota || $user->anggota->unit_id !== $laporan->anggota->unit_id) {
                throw new CustomException('Akses ditolak. Anda hanya dapat menghapus laporan unit Anda.', 403);
            }
        }

        // Proses Hapus File Fisik
        foreach ($laporan->lampiran as $lampiran) {
            if (Storage::disk('public')->exists($lampiran->path_file)) {
                Storage::disk('public')->delete($lampiran->path_file);
            }
        }

        $laporan->delete();

        return [
            'message' => 'Data laporan harian berhasil dihapus'
        ];
    }

    public function ListValidasi($perPage, $currentPage, $request): array
    {
        $user = Auth::user();

        $query = LaporanHarian::with([
            'anggota:id,nama,unit_id',
            'anggota.unit:id,nama',
            'kategoriPelanggaran:id,nama',
            'regulasi:id,judul'
        ])
            ->where('jenis', 'insiden')
            ->where('telah_dieskalasi', false)
            ->whereNull('divalidasi_oleh');

        if ($user->hasRole('super_admin')) {
        } else if ($user->hasRole('komandan_regu')) {

            if (!$user->anggota) {
                throw new CustomException('Akun Komandan tidak terhubung dengan data anggota/unit. Harap hubungi Admin.', 403);
            }

            $unitIdKomandan = $user->anggota->unit_id;

            $query->whereHas('anggota', function ($q) use ($unitIdKomandan) {
                $q->where('unit_id', $unitIdKomandan);
            });
        } else {
            throw new CustomException('Role Anda tidak memiliki akses ke validasi laporan.', 403);
        }

        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }

        $laporan = $query
            ->orderByRaw("FIELD(severity, 'tinggi', 'sedang', 'rendah') ASC")
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $currentPage);

        // 5. Transformasi Data
        $laporan->through(function ($item) {
            return [
                'id'             => $item->id,
                'tanggal'        => $item->created_at->format('d-m-Y H:i'),

                // Null Safe Operator (?->) mencegah error jika anggota terhapus
                'anggota_nama'   => $item->anggota?->nama ?? 'Personil Tidak Dikenal',
                'unit_nama'      => $item->anggota?->unit?->nama ?? '-',

                'jenis'          => $item->jenis,
                'severity'       => $item->severity,
                'catatan'        => $item->catatan,

                'lat'            => $item->lat,
                'lng'            => $item->lng,

                'kategori'       => $item->kategoriPelanggaran?->nama ?? '-',
                'regulasi'       => $item->regulasi?->judul ?? '-',
            ];
        });

        return [
            'message' => 'Data validasi insiden berhasil ditampilkan',
            'data' => [
                'current_page' => $laporan->currentPage(),
                'per_page'     => $laporan->perPage(),
                'total'        => $laporan->total(),
                'last_page'    => $laporan->lastPage(),
                'items'        => $laporan->items()
            ]
        ];
    }

    public function processDecision(array $data, int $id): array
    {
        $user = Auth::user();

        $laporan = LaporanHarian::with('anggota')->find($id);

        if (!$laporan) {
            throw new CustomException('Data laporan tidak ditemukan.', 404);
        }

        if ($user->hasRole('komandan_regu')) {
            if (!$user->anggota) {
                throw new CustomException('Akun Anda tidak terhubung dengan data keanggotaan.', 403);
            }

            if ($laporan->anggota->unit_id !== $user->anggota->unit_id) {
                throw new CustomException('Anda tidak memiliki akses untuk memvalidasi laporan dari unit lain.', 403);
            }
        }

        if ($laporan->status_validasi != 'menunggu') {
            throw new CustomException('Data tidak dapat diupdate karena status sudah bukan menunggu (Sudah diproses sebelumnya).', 400);
        }

        if ($data['status_validasi'] == 'ditolak') {
            if (empty($data['catatan_validasi'])) {
                throw new CustomException('Catatan/Alasan wajib diisi jika laporan ditolak.', 422);
            }
        }

        return DB::transaction(function () use ($laporan, $data, $user) {

            $laporan->status_validasi = $data['status_validasi'];
            $laporan->divalidasi_oleh = $user->id;
            if (isset($data['catatan_validasi']) && !empty($data['catatan_validasi'])) {
                $laporan->catatan_validasi = $data['catatan_validasi'];
            }

            $laporan->save();

            return [
                'message' => 'Laporan berhasil diperbarui menjadi ' . $laporan->status_validasi,
                'data'    => $laporan
            ];
        });
    }
}

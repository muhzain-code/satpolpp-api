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
                'severity'          => $item->severity, // rendah/sedang/tinggi
                'catatan'           => $item->catatan, // Ini pengganti 'lokasi' teks manual

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
            $data['created_by'] = Auth::id();

            $laporan = LaporanHarian::create([
                'anggota_id'        => $data['anggota_id'],
                'jenis'             => $data['jenis'] ?? 'aman',
                'catatan'           => $data['catatan'] ?? null,
                'lat'               => $data['lat'] ?? null,
                'lng'               => $data['lng'] ?? null,
                'status_validasi'   => 'menunggu',
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

            Log::error('Gagal menambah laporan harian', [
                'message' => $e->getMessage(),
            ]);
            if ($e instanceof CustomException) {
                throw $e;
            }
            throw new CustomException('Gagal menambah laporan harian', 422);
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

        // 1. BLOKIR AKSES ANGGOTA
        if ($user->hasRole('anggota_regu')) {
            throw new CustomException('Akses ditolak. Anggota tidak diizinkan mengubah laporan.', 403);
        }

        DB::beginTransaction();
        try {
            // Load anggota untuk pengecekan unit
            $laporan = LaporanHarian::with(['lampiran', 'anggota'])->find($id);

            if (!$laporan) {
                throw new CustomException('Data laporan harian tidak ditemukan', 404);
            }

            // 2. CEK AKSES KOMANDAN
            if ($user->hasRole('komandan_regu')) {
                if (!$user->anggota || $user->anggota->unit_id !== $laporan->anggota->unit_id) {
                    throw new CustomException('Akses ditolak. Anda hanya dapat mengubah laporan unit Anda.', 403);
                }
            }

            // Hapus Lampiran Lama (Jika ada request lampiran baru)
            if (!empty($data['lampiran'])) {
                foreach ($laporan->lampiran as $lampiran) {
                    if (Storage::disk('public')->exists($lampiran->path_file)) {
                        Storage::disk('public')->delete($lampiran->path_file);
                    }
                    $lampiran->delete();
                }
            }

            // Update Data Utama
            $laporan->update([
                'jenis'           => $data['jenis'] ?? $laporan->jenis,
                'catatan'         => $data['catatan'] ?? $laporan->catatan,
                'lat'             => $data['lat'] ?? $laporan->lat,
                'lng'             => $data['lng'] ?? $laporan->lng,
                'status_validasi' => $data['status_validasi'] ?? $laporan->status_validasi,
                'divalidasi_oleh' => $data['divalidasi_oleh'] ?? $laporan->divalidasi_oleh,
                // Best practice: updated_at otomatis dihandle Eloquent, tapi manual oke
                'updated_at'      => now(),
            ]);

            // Upload Lampiran Baru
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
                'data'    => $laporan->load('lampiran')
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
}

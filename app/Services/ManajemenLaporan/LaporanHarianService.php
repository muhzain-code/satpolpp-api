<?php

namespace App\Services\ManajemenLaporan;

use Illuminate\Http\UploadedFile;
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

    // Inject OptimizePhotoService
    public function __construct(OptimizePhotoService $optimizeService)
    {
        $this->optimizeService = $optimizeService;
    }

    /**
     * Menampilkan semua data dengan filter dan pagination
     */
    public function getAll(int $perPage, int $currentPage, $request): array
    {
        $user = Auth::user();

        // 1. Base Query dengan Eager Loading
        $query = LaporanHarian::with([
            'anggota.unit',
            'kategoriPelanggaran',
            'validator:id,name', // Hanya ambil id & nama validator
            'regulasi'
        ])->orderBy('created_at', 'desc');

        // 2. Terapkan Filter Berdasarkan Role (Scope Data)
        if ($user->hasRole('komandan_regu')) {
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
        }

        // 3. Terapkan Filter Request (Search & Filtering)
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

        $filters = ['jenis', 'severity', 'urgensi', 'status_validasi'];
        foreach ($filters as $filter) {
            if ($request->has($filter) && $request->$filter != null) {
                $query->where($filter, $request->$filter);
            }
        }

        if ($request->has('telah_dieskalasi') && $request->telah_dieskalasi !== null) {
            $val = filter_var($request->telah_dieskalasi, FILTER_VALIDATE_BOOLEAN);
            $query->where('telah_dieskalasi', $val);
        }

        // 4. Eksekusi Pagination
        $laporan = $query->paginate($perPage, ['*'], 'page', $currentPage);

        // 5. Transform Data (Return Array Bersih)
        return [
            'message' => 'Data laporan berhasil ditampilkan',
            'data' => [
                'current_page' => $laporan->currentPage(),
                'per_page'     => $laporan->perPage(),
                'total'        => $laporan->total(),
                'last_page'    => $laporan->lastPage(),
                'items'        => $laporan->getCollection()->transform(function ($item) {
                    return [
                        'id'              => $item->id,
                        'tanggal'         => $item->created_at->format('d-m-Y H:i'),
                        'anggota_nama'    => $item->anggota->nama ?? '-',
                        // 'unit_nama'       => $item->anggota->unit->nama ?? '-',

                        // Detail Laporan
                        'jenis'           => $item->jenis,
                        'urgensi'         => $item->urgent ?? 'non-urgent', // urgent / non-urgent
                        'severity'        => $item->severity ?? '-',
                        // 'catatan'         => $item->catatan,
                        // 'lokasi_lat_lng'  => ($item->lat && $item->lng) ? "{$item->lat}, {$item->lng}" : '-',

                        // Pelanggaran (Jika ada)
                        'kategori'        => $item->kategoriPelanggaran->nama ?? '-',
                        // 'regulasi'        => $item->regulasi->judul ?? '-',

                        // Status & Validasi
                        'status_validasi' => $item->status_validasi,
                        'validator'       => $item->validator->name ?? '-',
                        // 'eskalasi'        => $item->telah_dieskalasi ? 'Ya' : 'Tidak',
                    ];
                }),
            ]
        ];
    }

    /**
     * Menampilkan Detail Laporan Harian
     */
    public function getById($id): array
    {
        $user = Auth::user();

        $laporan = LaporanHarian::with(['lampiran', 'anggota.unit', 'validator', 'regulasi', 'kategoriPelanggaran'])
            ->find($id);

        if (!$laporan) {
            throw new CustomException('Laporan harian tidak ditemukan', 404);
        }

        // --- SECURITY CHECK KOMANDAN REGU ---
        if ($user->hasRole('komandan_regu')) {
            if (!$user->anggota) {
                throw new CustomException('Akun Komandan tidak valid.', 403);
            }
            if ($user->anggota->unit_id !== $laporan->anggota->unit_id) {
                throw new CustomException('Akses ditolak. Anda hanya bisa melihat laporan anggota unit Anda.', 403);
            }
        }

        // --- SECURITY CHECK ANGGOTA REGU ---
        if ($user->hasRole('anggota_regu')) {
            if (!$user->anggota) {
                throw new CustomException('Akun Anggota tidak valid.', 403);
            }
            if ($laporan->anggota_id !== $user->anggota->id) {
                throw new CustomException('Akses ditolak. Anda hanya bisa melihat laporan Anda sendiri.', 403);
            }
        }

        // Transformasi Output Detail
        $data = [
            'id'             => $laporan->id,
            'created_at'     => $laporan->created_at->format('Y-m-d H:i:s'),

            // Personil
            'anggota' => [
                'id'   => $laporan->anggota_id,
                'nama' => $laporan->anggota->nama ?? '-',
                'unit' => $laporan->anggota->unit->nama ?? '-',
            ],

            // Konten Laporan
            'jenis'          => $laporan->jenis,
            'urgensi'        => $laporan->urgensi, // urgent / non-urgent
            'catatan'        => $laporan->catatan,
            'lat'            => $laporan->lat,
            'lng'            => $laporan->lng,

            // Detail Pelanggaran
            'kategori_pelanggaran' => $laporan->kategoriPelanggaran->nama ?? null,
            'regulasi_indikatif'   => $laporan->regulasi->judul ?? null,
            'severity'             => $laporan->severity,

            // Validasi Info
            'validasi' => [
                'status'           => $laporan->status_validasi,
                'catatan'          => $laporan->catatan_validasi, // Note dari komandan
                'divalidasi_oleh'  => $laporan->validator->name ?? null,
                'tanggal_validasi' => $laporan->tanggal_validasi,
            ],

            // Lampiran
            'lampiran' => $laporan->lampiran->map(function ($item) {
                return [
                    'id'        => $item->id,
                    'nama_file' => $item->nama_file,
                    'jenis'     => $item->jenis,
                    'url'       => url(Storage::url($item->path_file))
                ];
            })
        ];

        return [
            'message' => 'Detail laporan berhasil ditampilkan',
            'data'    => $data
        ];
    }

    /**
     * Membuat Laporan Harian Baru
     * Alur: 
     * 1. Anggota buat laporan.
     * 2. Default status 'menunggu' (perlu validasi komandan), kecuali Superadmin.
     */
    public function store(array $data): array
    {
        DB::beginTransaction();
        try {
            $user = Auth::user();

            // 1. Tentukan Anggota ID
            $anggotaId = null;
            $isSuperAdmin = $user->hasRole('super_admin');

            if ($isSuperAdmin) {
                if (empty($data['anggota_id'])) throw new CustomException('Superadmin wajib memilih anggota.');
                $anggotaId = $data['anggota_id'];
            } else {
                if (!$user->anggota || $user->anggota->status !== 'aktif') {
                    throw new CustomException('Akun anggota tidak valid/aktif.');
                }
                $anggotaId = $user->anggota->id;
            }

            // 2. Set Status Validasi Default
            $statusValidasi = 'menunggu';
            $divalidasiOleh = null;
            $tanggalValidasi = null;

            // Jika Superadmin yang input, bisa langsung disetujui
            if ($isSuperAdmin && isset($data['status_validasi'])) {
                $statusValidasi = $data['status_validasi'];
                if (in_array($statusValidasi, ['disetujui', 'ditolak'])) {
                    $divalidasiOleh = $user->id;
                    $tanggalValidasi = now();
                }
            }

            // 3. Logic Incident Fields
            $kategoriId = null;
            $regulasiId = null;
            $severity   = null;
            $urgensi    = 'non-urgent';
            if (isset($data['jenis']) && $data['jenis'] === 'insiden') {
                $kategoriId = $data['kategori_pelanggaran_id'] ?? null;
                $regulasiId = $data['regulasi_indikatif_id'] ?? null;
                $severity   = $data['severity'] ?? null;
                $urgensi    = $data['urgent'] ?? 'non-urgent';
                
                if (!$severity) throw new CustomException('Severity wajib diisi untuk insiden.');
            }
            // dd($urgensi);

            // 4. Create Data
            $laporan = LaporanHarian::create([
                'anggota_id'              => $anggotaId,
                'jenis'                   => $data['jenis'] ?? 'aman',
                'urgent'                 => $urgensi,
                'catatan'                 => $data['catatan'] ?? null,
                'lat'                     => $data['lat'] ?? null,
                'lng'                     => $data['lng'] ?? null,
                'kategori_pelanggaran_id' => $kategoriId,
                'regulasi_indikatif_id'   => $regulasiId,
                'severity'                => $severity,
                'status_validasi'         => $statusValidasi,
                'divalidasi_oleh'         => $divalidasiOleh,
                'tanggal_validasi'        => $tanggalValidasi,
                'created_by'              => $user->id,
            ]);

            // 5. Handle Lampiran (Optimize)
            if (!empty($data['lampiran']) && is_array($data['lampiran'])) {
                foreach ($data['lampiran'] as $file) {
                    if ($file instanceof UploadedFile && $file->isValid()) {
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
            throw new CustomException('Gagal menambah laporan: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update Laporan Harian
     * Hanya Superadmin atau Anggota (jika status masih menunggu/revisi)
     */
    public function update($id, array $data): array
    {
        DB::beginTransaction();
        try {
            $user = Auth::user();
            $laporan = LaporanHarian::with(['lampiran'])->findOrFail($id);

            // Cek Otoritas Edit
            if (!$user->hasRole('super_admin')) {
                // Jika anggota biasa, hanya bisa edit milik sendiri DAN jika status revisi/menunggu
                if ($laporan->anggota_id !== $user->anggota->id) {
                    throw new CustomException('Akses ditolak.', 403);
                }
                if ($laporan->status_validasi === 'disetujui') {
                    throw new CustomException('Laporan yang sudah disetujui tidak dapat diedit.', 403);
                }
            }

            // Hapus Lampiran Lama jika ada request hapus (opsional, tergantung FE)
            // Di sini saya asumsikan replace logic atau add logic via API terpisah
            // Tapi mengikuti contoh, kita cek jika ada input lampiran baru, yang lama dihapus (simple logic)
            if (!empty($data['lampiran'])) {
                foreach ($laporan->lampiran as $lampiran) {
                    if (Storage::disk('public')->exists($lampiran->path_file)) {
                        Storage::disk('public')->delete($lampiran->path_file);
                    }
                    $lampiran->delete();
                }
            }

            // Update Fields
            $laporan->update([
                'jenis'                   => $data['jenis'] ?? $laporan->jenis,
                'urgensi'                 => $data['urgensi'] ?? $laporan->urgensi,
                'catatan'                 => $data['catatan'] ?? $laporan->catatan,
                'lat'                     => $data['lat'] ?? $laporan->lat,
                'lng'                     => $data['lng'] ?? $laporan->lng,
                'kategori_pelanggaran_id' => $data['kategori_pelanggaran_id'] ?? $laporan->kategori_pelanggaran_id,
                'regulasi_indikatif_id'   => $data['regulasi_indikatif_id'] ?? $laporan->regulasi_indikatif_id,
                'severity'                => $data['severity'] ?? $laporan->severity,
                // Reset status validasi ke menunggu jika diedit anggota (flow revisi)
                'status_validasi'         => $user->hasRole('super_admin') ? ($data['status_validasi'] ?? $laporan->status_validasi) : 'menunggu',
            ]);

            // Add Lampiran Baru
            if (!empty($data['lampiran'])) {
                foreach ($data['lampiran'] as $file) {
                    if ($file instanceof UploadedFile && $file->isValid()) {
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
                'message' => 'Laporan berhasil diperbarui',
                'data'    => $laporan->fresh()->load('lampiran')
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Update Laporan Error', ['msg' => $e->getMessage()]);
            throw new CustomException('Gagal update laporan: ' . $e->getMessage(), 500);
        }
    }

    /**
     * [BARU] Validasi Laporan oleh Komandan
     * Alur: 
     * - Disetujui -> Selesai / Lanjut Penindakan (trigger di FE/Flow berikutnya)
     * - Direvisi -> Kembali ke anggota
     * - Ditolak -> Selesai
     */
    public function validasiKomandan($id, array $data): array
    {
        DB::beginTransaction();
        try {
            $user = Auth::user();
            $laporan = LaporanHarian::findOrFail($id);

            // 1. Cek Role Komandan
            if (!$user->hasRole('komandan_regu') && !$user->hasRole('super_admin')) {
                throw new CustomException('Hanya Komandan atau Superadmin yang dapat memvalidasi.', 403);
            }

            // 2. Cek Akses Unit (Jika Komandan)
            if ($user->hasRole('komandan_regu')) {
                if ($user->anggota->unit_id !== $laporan->anggota->unit_id) {
                    throw new CustomException('Anda hanya dapat memvalidasi laporan anggota unit Anda.', 403);
                }
            }

            $status = $data['status_validasi']; // disetujui, ditolak, direvisi

            if (!in_array($status, ['disetujui', 'ditolak', 'direvisi'])) {
                throw new CustomException('Status validasi tidak valid.', 422);
            }

            // 3. Update Status
            $laporan->update([
                'status_validasi'  => $status,
                'catatan_validasi' => $data['catatan_validasi'] ?? null, // Alasan revisi/tolak
                'divalidasi_oleh'  => $user->id,
                'tanggal_validasi' => now(),
            ]);

            DB::commit();

            return [
                'message' => 'Validasi laporan berhasil disimpan. Status: ' . $status,
                'data'    => $laporan
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Validasi Laporan Error', ['msg' => $e->getMessage()]);
            throw new CustomException('Gagal memvalidasi laporan: ' . $e->getMessage(), 500);
        }
    }

    public function delete($id): array
    {
        $user = Auth::user();
        $laporan = LaporanHarian::with('lampiran')->find($id);

        if (!$laporan) {
            throw new CustomException('Laporan tidak ditemukan', 404);
        }

        // Cek Permission Delete
        if ($user->hasRole('anggota_regu')) {
            throw new CustomException('Anggota tidak diizinkan menghapus laporan.', 403);
        }

        if ($user->hasRole('komandan_regu')) {
            if ($user->anggota->unit_id !== $laporan->anggota->unit_id) {
                throw new CustomException('Akses ditolak.', 403);
            }
        }

        // Hapus File Fisik
        foreach ($laporan->lampiran as $lampiran) {
            if (Storage::disk('public')->exists($lampiran->path_file)) {
                Storage::disk('public')->delete($lampiran->path_file);
            }
        }

        $laporan->delete();

        return ['message' => 'Laporan berhasil dihapus'];
    }
}

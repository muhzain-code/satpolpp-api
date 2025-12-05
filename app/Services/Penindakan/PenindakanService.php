<?php

namespace App\Services\Penindakan;

use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use App\Exceptions\CustomException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\Penindakan\Penindakan;
use App\Services\BAPGeneratorService;
use App\Services\OptimizePhotoService;
use Illuminate\Support\Facades\Storage;
use App\Models\Penindakan\PenindakanAnggota;
use App\Models\Penindakan\PenindakanLampiran;

class PenindakanService
{
    protected $optimizeService;

    // Inject OptimizePhotoService
    public function __construct(OptimizePhotoService $optimizeService)
    {
        $this->optimizeService = $optimizeService;
    }

    public function getAll($filter): array
    {
        $user = Auth::user();

        // Base Query dengan relasi minim untuk list view
        $query = Penindakan::with([
            'operasi:id,judul',
            'pengaduan:id,nomor_tiket',
            'anggotaPelapor:id,nama,unit_id' // Load unit_id pelapor sekalian jika perlu debug, tapi di whereHas sudah cukup
        ])->orderBy('created_at', 'desc');

        // --- FILTER LOGIC KOMANDAN REGU ---
        if ($user->hasRole('komandan_regu')) {
            // Pastikan user terhubung dengan data anggota
            if (!$user->anggota) {
                // Jika user login tidak punya data anggota, anggap tidak punya akses ke data unit manapun
                throw new CustomException('Akun Anda tidak terhubung dengan data Anggota.', 403);
            }

            $unitIdKomandan = $user->anggota->unit_id;

            // Filter: Hanya tampilkan penindakan dimana pelapornya memiliki unit_id yang sama dengan komandan
            $query->whereHas('anggotaPelapor', function ($q) use ($unitIdKomandan) {
                $q->where('unit_id', $unitIdKomandan);
            });
        }

        // --- FILTER LOGIC ANGGOTA REGU ---
        if ($user->hasRole('anggota_regu')) {
            // Anggota hanya melihat laporan yang dibuatnya sendiri
            $query->where('anggota_pelapor_id', $user->id);
        }

        // Filter: Status Validasi Komandan
        if (isset($filter['status_validasi_komandan'])) {
            $query->where('status_validasi_komandan', $filter['status_validasi_komandan']);
        }

        // Filter: Status Validasi PPNS
        if (isset($filter['status_validasi_ppns'])) {
            $query->where('status_validasi_ppns', strtolower($filter['status_validasi_ppns']));
        }

        $penindakan = $query->paginate($filter['per_page'] ?? 10, ['*'], 'page', $filter['page'] ?? 1);

        return [
            'message' => 'Daftar penindakan berhasil ditampilkan',
            'data' => [
                'current_page' => $penindakan->currentPage(),
                'per_page'     => $penindakan->perPage(),
                'total'        => $penindakan->total(),
                'last_page'    => $penindakan->lastPage(),
                'items'        => $penindakan->items(),
            ]
        ];
    }

    /**
     * Menampilkan detail penindakan
     */
    public function getById($id): array
    {
        $user = Auth::user();

        // Eager Load relasi lengkap
        $query = Penindakan::with([
            'penindakanLampiran',
            'penindakanRegulasi.regulasi',
            'penindakanAnggota.anggota',
            'creator',
            'kecamatan',
            'desa',
            'anggotaPelapor' // Pastikan ini diload untuk pengecekan unit
        ]);

        $penindakan = $query->find($id);

        if (!$penindakan) {
            throw new CustomException('Penindakan tidak ditemukan', 404);
        }

        // --- SECURITY CHECK KOMANDAN REGU ---
        if ($user->hasRole('komandan_regu')) {
            if (!$user->anggota) {
                throw new CustomException('Akun Anda tidak terhubung dengan data Anggota.', 403);
            }

            $unitIdKomandan = $user->anggota->unit_id;
            $unitIdPelapor  = $penindakan->anggotaPelapor->unit_id ?? null;

            // Jika unit tidak sama, tolak akses
            if ($unitIdKomandan != $unitIdPelapor) {
                throw new CustomException('Anda tidak memiliki akses ke laporan dari unit lain.', 403);
            }
        }

        // --- SECURITY CHECK ANGGOTA REGU ---
        if ($user->hasRole('anggota_regu')) {
            // Anggota hanya bisa melihat detail laporannya sendiri
            if ($penindakan->anggota_pelapor_id != $user->id) {
                throw new CustomException('Anda tidak memiliki akses ke laporan ini.', 403);
            }
        }

        // Transformasi Data ke Response API yang rapi
        $data = [
            'id'                => $penindakan->id,
            'jenis_penindakan'  => $penindakan->jenis_penindakan,
            'uraian'            => $penindakan->uraian,

            // Sumber Data
            'operasi_id'        => $penindakan->operasi_id,
            'laporan_harian_id' => $penindakan->laporan_harian_id,
            'pengaduan_id'      => $penindakan->pengaduan_id,

            // Pelapor
            'nama_pelapor'      => $penindakan->anggotaPelapor->nama ?? '-',
            'unit_pelapor'      => $penindakan->anggotaPelapor->unit_id ?? '-', // Opsional: tampilkan unit

            // Lokasi
            'kecamatan_id'      => $penindakan->kecamatan_id,
            'nama_kecamatan'    => $penindakan->kecamatan->nama_kecamatan ?? '-',
            'desa_id'           => $penindakan->desa_id,
            'nama_desa'         => $penindakan->desa->nama_desa ?? '-',
            'lokasi_detail'     => $penindakan->lokasi_detail,
            'lat'               => $penindakan->lat,
            'lng'               => $penindakan->lng,

            // --- TIER 1: Validasi Komandan ---
            'validasi_komandan' => [
                'status'    => $penindakan->status_validasi_komandan,
                'catatan'   => $penindakan->catatan_validasi_komandan,
                'validator' => $penindakan->komandan_validator_id,
                'tanggal'   => $penindakan->tanggal_validasi_komandan,
            ],

            // --- TIER 2: Validasi PPNS ---
            'validasi_ppns' => [
                'butuh_validasi' => (bool) $penindakan->butuh_validasi_ppns,
                'status'         => $penindakan->status_validasi_ppns,
                'catatan'        => $penindakan->catatan_validasi_ppns,
                'validator'      => $penindakan->ppns_validator_id,
                'tanggal'        => $penindakan->tanggal_validasi_ppns,
            ],

            // Audit Info
            'created_by_name'   => $penindakan->creator->name ?? null,
            'created_at'        => $penindakan->created_at->format('Y-m-d H:i:s'),

            // List Data Relasi
            'list_regulasi'     => $penindakan->penindakanRegulasi->map(function ($item) {
                return [
                    'id'    => $item->regulasi->id ?? '-',
                    'kode'  => $item->regulasi->kode ?? '-',
                    'judul' => $item->regulasi->judul ?? '-',
                    'pasal' => $item->pasal_dilanggar,
                ];
            })->toArray(),

            'list_lampiran'     => $penindakan->penindakanLampiran->map(function ($item) {
                return [
                    'jenis' => $item->jenis,
                    'url'   => url(Storage::url($item->path_file)),
                ];
            })->toArray(),

            'list_penindakan_anggota' => $penindakan->penindakanAnggota->map(function ($item) {
                return [
                    'id'    => $item->anggota->id ?? '-',
                    'nama'  => $item->anggota->nama ?? '-',
                    'peran' => $item->peran ?? '-',
                ];
            })->toArray(),
        ];

        return [
            'message' => 'Detail penindakan berhasil ditampilkan',
            'data'    => $data
        ];
    }

    /**
     * Membuat Penindakan Baru (Status Default: Menunggu Komandan)
     */
    public function create(array $data): array
    {
        try {
            $user = Auth::user();

            // 1. Tentukan Pelapor (User Login / Inputan)
            $pelaporId = $user->anggota_id ?? ($data['anggota_pelapor_id'] ?? null);
            $data['anggota_pelapor_id'] = $pelaporId;

            // 2. Masukkan pelapor ke array 'anggota' pivot agar tercatat peran-nya
            $anggotaIds = $data['anggota'] ?? [];
            if ($pelaporId && !in_array($pelaporId, $anggotaIds)) {
                $anggotaIds[] = $pelaporId;
                if (!isset($data['peran'][$pelaporId])) {
                    $data['peran'][$pelaporId] = 'Pelapor';
                }
            }
            $data['anggota'] = $anggotaIds;

            return DB::transaction(function () use ($data, $user) {

                // A. Create Parent Record
                // Catatan: 'status_validasi_komandan' default 'menunggu' di DB
                // 'butuh_validasi_ppns' default false di DB
                // 'status_validasi_ppns' default null di DB

                $penindakan = Penindakan::create([
                    'operasi_id'         => $data['operasi_id'] ?? null,
                    'pengaduan_id'       => $data['pengaduan_id'] ?? null,
                    'laporan_harian_id'  => $data['laporan_harian_id'] ?? null,
                    'anggota_pelapor_id' => $data['anggota_pelapor_id'],
                    'jenis_penindakan'   => $data['jenis_penindakan'],
                    'uraian'             => $data['uraian'] ?? null,
                    'kecamatan_id'       => $data['kecamatan_id'] ?? null,
                    'desa_id'            => $data['desa_id'] ?? null,
                    'lokasi_detail'      => $data['lokasi_detail'] ?? null, // SESUAI SCHEMA
                    'lat'                => $data['lat'] ?? null,
                    'lng'                => $data['lng'] ?? null,
                    'created_by'         => $user->id,
                ]);

                if (!$penindakan) {
                    throw new CustomException('Gagal menyimpan data penindakan', 422);
                }

                // B. Regulasi
                if (!empty($data['regulasi'])) {
                    $penindakan->penindakanRegulasi()->createMany($data['regulasi']);
                }

                // C. Anggota Pivot
                if (!empty($data['anggota'])) {
                    foreach ($data['anggota'] as $anggotaId) {
                        $penindakan->penindakanAnggota()->create([
                            'anggota_id' => $anggotaId,
                            'peran'      => $data['peran'][$anggotaId] ?? null,
                            'created_by' => $user->id,
                        ]);
                    }
                }

                // D. Lampiran (Optimize Image)
                if (!empty($data['lampiran']) && is_array($data['lampiran'])) {
                    foreach ($data['lampiran'] as $item) {
                        $file = $item['file'] ?? null;
                        if ($file instanceof UploadedFile) {
                            // Optimize
                            $path = $this->optimizeService->optimizeImage($file, 'penindakan');

                            $penindakan->penindakanLampiran()->create([
                                'nama_file'  => $item['nama_file'] ?? $file->getClientOriginalName(),
                                'path_file'  => $path,
                                'jenis'      => 'foto',
                                'created_by' => $user->id,
                            ]);
                        }
                    }
                }

                return [
                    'success' => true,
                    'message' => 'Laporan penindakan berhasil dibuat. Menunggu validasi Komandan.',
                    'data'    => $penindakan->load('penindakanRegulasi', 'penindakanLampiran'),
                ];
            });
        } catch (Exception $e) {
            Log::error('Create Penindakan Error', ['msg' => $e->getMessage()]);
            throw new CustomException('Gagal menambah penindakan: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update Data Fisik Penindakan (Hanya bisa diedit jika belum final)
     */
    public function update($id, array $data): array
    {
        try {
            return DB::transaction(function () use ($id, $data) {
                $penindakan = Penindakan::findOrFail($id);
                $user = Auth::user();

                // Cek apakah sudah dikunci oleh validasi (Opsional)
                if ($penindakan->status_validasi_komandan === 'disetujui' && $penindakan->status_validasi_ppns === 'disetujui') {
                    throw new CustomException('Data sudah final dan tidak dapat diubah.', 403);
                }

                // Update Parent
                $penindakan->update([
                    'uraian'             => $data['uraian'] ?? $penindakan->uraian,
                    'jenis_penindakan'   => $data['jenis_penindakan'] ?? $penindakan->jenis_penindakan,
                    'lokasi_detail'      => $data['lokasi_detail'] ?? $penindakan->lokasi_detail,
                    'lat'                => $data['lat'] ?? $penindakan->lat,
                    'lng'                => $data['lng'] ?? $penindakan->lng,
                    'updated_by'         => $user->id,
                ]);

                // Update Lampiran (Logic Hapus & Tambah)
                if (isset($data['lampiran']) && is_array($data['lampiran'])) {
                    // 1. Keep Existing
                    $keptPaths = array_filter(array_column($data['lampiran'], 'path_file'));
                    $filesToDelete = $penindakan->penindakanLampiran()
                        ->whereNotIn('path_file', $keptPaths)
                        ->get();

                    foreach ($filesToDelete as $oldFile) {
                        Storage::disk('public')->delete($oldFile->path_file);
                        $oldFile->delete();
                    }

                    // 2. Add New
                    foreach ($data['lampiran'] as $item) {
                        $file = $item['file'] ?? null;
                        if ($file instanceof UploadedFile) {
                            $path = $this->optimizeService->optimizeImage($file, 'penindakan');
                            $penindakan->penindakanLampiran()->create([
                                'nama_file'  => $item['nama_file'] ?? $file->getClientOriginalName(),
                                'path_file'  => $path,
                                'jenis'      => 'foto',
                                'created_by' => $user->id,
                            ]);
                        }
                    }
                }

                // Note: Update Regulasi & Anggota bisa ditambahkan logic serupa di sini

                return [
                    'success' => true,
                    'message' => 'Penindakan berhasil diperbarui',
                    'data'    => $penindakan->fresh()->load('penindakanLampiran'),
                ];
            });
        } catch (Exception $e) {
            Log::error('Update Penindakan Error', ['msg' => $e->getMessage()]);
            throw new CustomException('Gagal update penindakan', 500);
        }
    }

    /**
     * [BARU] Tier 1: Validasi Komandan
     * Menentukan apakah kasus berhenti (teguran) atau lanjut ke PPNS.
     */
    public function validasiKomandan($id, array $data): array
    {
        try {
            return DB::transaction(function () use ($id, $data) {
                $penindakan = Penindakan::findOrFail($id);
                $user = Auth::user();

                // Pastikan user adalah Komandan (Pengecekan Role)
                // if (!$user->hasRole('komandan_regu')) { throw ... }

                $status = $data['status_validasi_komandan']; // 'disetujui', 'ditolak', 'revisi'

                if (!in_array($status, ['disetujui', 'ditolak', 'revisi'])) {
                    throw new CustomException('Status validasi tidak valid', 422);
                }

                $updatePayload = [
                    'status_validasi_komandan'  => $status,
                    'catatan_validasi_komandan' => $data['catatan_validasi_komandan'] ?? null,
                    'komandan_validator_id'     => $user->id,
                    'tanggal_validasi_komandan' => now(),
                ];

                // LOGIC: SAKLAR KE PPNS
                if ($status === 'disetujui') {
                    // Komandan menentukan apakah perlu diteruskan ke PPNS
                    $butuhPpns = filter_var($data['butuh_validasi_ppns'] ?? false, FILTER_VALIDATE_BOOLEAN);

                    $updatePayload['butuh_validasi_ppns'] = $butuhPpns;

                    if ($butuhPpns) {
                        // Jika butuh, set status PPNS ke 'menunggu' agar muncul di dashboard PPNS
                        $updatePayload['status_validasi_ppns'] = 'menunggu';
                    } else {
                        // Jika selesai di tempat, pastikan status PPNS null
                        $updatePayload['status_validasi_ppns'] = null;
                    }
                } else {
                    // Jika ditolak/revisi, reset flow PPNS
                    $updatePayload['butuh_validasi_ppns'] = false;
                    $updatePayload['status_validasi_ppns'] = null;
                }

                $penindakan->update($updatePayload);
                
                return [
                    'message' => 'Validasi Komandan berhasil disimpan. Status: ' . $status,
                    'data'    => $penindakan
                ];
            });
        } catch (Exception $e) {
            Log::error('Validasi Komandan Error', ['msg' => $e->getMessage()]);
            throw new CustomException('Gagal memproses validasi komandan', 500);
        }
    }

    /**
     * Tier 2: Validasi PPNS
     * Hanya bisa dilakukan jika Komandan setuju DAN butuh_validasi_ppns = true
     */
    public function validasiPPNS($id, array $data): array
    {
        try {
            return DB::transaction(function () use ($id, $data) {
                $penindakan = Penindakan::find($id);

                if (!$penindakan) {
                    throw new CustomException('Penindakan tidak ditemukan', 404);
                }

                // 1. Cek Validasi Komandan
                if ($penindakan->status_validasi_komandan !== 'disetujui') {
                    throw new CustomException('Validasi ditolak: Penindakan belum disetujui oleh Komandan.', 422);
                }

                // 2. Cek Saklar PPNS
                if (!$penindakan->butuh_validasi_ppns) {
                    throw new CustomException('Validasi ditolak: Penindakan ini diputuskan selesai di tingkat Komandan (Tidak butuh PPNS).', 422);
                }

                // 3. Cek Status Input
                $status = $data['status_validasi_ppns'];
                if (!in_array($status, ['disetujui', 'ditolak', 'revisi'])) {
                    throw new CustomException('Status validasi harus disetujui, ditolak, atau revisi.', 422);
                }

                $penindakan->update([
                    'status_validasi_ppns'  => $status,
                    'catatan_validasi_ppns' => $data['catatan_validasi_ppns'] ?? null,
                    'ppns_validator_id'     => Auth::id(),
                    'tanggal_validasi_ppns' => now(),
                ]);

                // Opsional: Generate BAP otomatis jika disetujui PPNS
                // if ($status === 'disetujui') { ... }

                return [
                    'message' => 'Validasi PPNS berhasil disimpan. Status: ' . $status,
                    'data'    => $penindakan,
                ];
            });
        } catch (Exception $e) {
            Log::error('Validasi PPNS Error', ['msg' => $e->getMessage()]);
            throw new CustomException($e->getMessage(), $e->getCode() ?: 500);
        }
    }
}

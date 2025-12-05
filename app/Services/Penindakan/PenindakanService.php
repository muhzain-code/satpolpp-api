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

    // 1. Inject OptimizePhotoService
    public function __construct(OptimizePhotoService $optimizeService)
    {
        $this->optimizeService = $optimizeService;
    }

    public function getAll($filter): array
    {
        $user = Auth::user();

        if ($user->hasRole('super_admin') ||  $user->hasRole('ppns')) {
            $query = Penindakan::with(['operasi:id,judul', 'pengaduan:id,nomor_tiket'])->orderBy('created_at', 'desc');
        }

        if ($user->hasRole('komandan_regu')) {
            $query = Penindakan::with(['operasi:id,judul', 'pengaduan:id,nomor_tiket'])->where('created_by', $user->id)->orderBy('created_at', 'desc');
        }

        if (isset($filter['status_validasi_ppns'])) {
            $query->where('status_validasi_ppns', strtolower($filter['status_validasi_ppns']));
        }

        $penindakan = $query->paginate($filter['per_page'], ['*'], 'page', $filter['page']);

        return [
            'message' => 'Penindakan berhasil ditampilkan',
            'data' => [
                'current_page' => $penindakan->currentPage(),
                'per_page' => $penindakan->perPage(),
                'total' => $penindakan->total(),
                'last_page' => $penindakan->lastPage(),
                'items' => $penindakan->items(),
            ]
        ];
    }
    public function getById($id): array
    {
        $user = Auth::user();

        // Eager Load relasi yang diperlukan
        $query = Penindakan::with([
            'penindakanLampiran',
            'penindakanRegulasi.regulasi',
            'penindakanAnggota.anggota',
            'creator',
            'kecamatan',
            'desa',
        ]);

        // Logika Hak Akses
        $penindakan = null;
        if ($user->hasRole('super_admin') || $user->hasRole('ppns')) {
            $penindakan = $query->find($id);
        } elseif ($user->hasRole('komandan_regu')) {
            $penindakan = $query->where('created_by', $user->id)->find($id);
        }

        if (!$penindakan) {
            throw new CustomException('Penindakan tidak ditemukan', 404);
        }

        // Transformasi Flat (Satu Baris)
        $data = [
            'id'                    => $penindakan->id,
            'jenis_penindakan'      => $penindakan->jenis_penindakan,
            'uraian'                => $penindakan->uraian,

            // Sumber Data (Flat)
            'operasi_id'            => $penindakan->operasi_id,
            'laporan_harian_id'     => $penindakan->laporan_harian_id,
            'pengaduan_id'          => $penindakan->pengaduan_id,

            'nama_pelapor'          => $penindakan->anggotaPelapor->nama,

            // Lokasi (Flat)
            'kecamatan_id'          => $penindakan->kecamatan_id,
            'nama_kecamatan'          => $penindakan->kecamatan->nama_kecamatan,
            'desa_id'               => $penindakan->desa_id,
            'nama_desa'          => $penindakan->desa->nama_desa,
            'lokasi_alamat'         => $penindakan->lokasi,
            'lokasi_lat'            => $penindakan->lat,
            'lokasi_lng'            => $penindakan->lng,

            // Validasi PPNS (Flat)
            'validasi_butuh'        => (bool) $penindakan->butuh_validasi_ppns,
            'validasi_status'       => $penindakan->status_validasi_ppns,
            'validasi_catatan'      => $penindakan->catatan_validasi_ppns,
            'validasi_validator_id' => $penindakan->ppns_validator_id,
            'validasi_tanggal'      => $penindakan->tanggal_validasi_ppns,

            // Audit Info
            'created_by_name'       => $penindakan->creator->name ?? null,
            'created_at'            => $penindakan->created_at->format('Y-m-d H:i:s'),

            // List Data (Tetap array list, tapi properti di dalamnya simpel)
            'list_regulasi'         => $penindakan->penindakanRegulasi->map(function ($item) {
                return [
                    'id' => $item->regulasi->id ?? '-',
                    'kode'  => $item->regulasi->kode ?? '-',
                    'judul' => $item->regulasi->judul ?? '-',
                    'pasal' => $item->pasal_dilanggar,
                ];
            })->toArray(),

            'list_lampiran'         => $penindakan->penindakanLampiran->map(function ($item) {
                return [
                    'jenis' => $item->jenis,
                    'url'   => url(Storage::url($item->path_file)),
                ];
            })->toArray(),

            'list_penindakan_anggota'         => $penindakan->penindakanAnggota->map(function ($item) {
                return [
                    'id' => $item->anggota->id ?? '-',
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

    public function create(array $data): array
    {
        try {
            $user = Auth::user();

            // --- 1. DATA PREPARATION ---
            // Tentukan Pelapor (Prioritas: Anggota Login -> Inputan)
            $pelaporId = $user->anggota_id ?? ($data['anggota_pelapor_id'] ?? null);
            $data['anggota_pelapor_id'] = $pelaporId;

            // Pastikan pelapor masuk ke list Anggota (Pivot)
            $anggotaIds = $data['anggota'] ?? [];
            if ($pelaporId && !in_array($pelaporId, $anggotaIds)) {
                $anggotaIds[] = $pelaporId;
                if (!isset($data['peran'][$pelaporId])) {
                    $data['peran'][$pelaporId] = 'Pelapor';
                }
            }
            $data['anggota'] = $anggotaIds;

            // Status PPNS
            $statusPpns = ($data['butuh_validasi_ppns'] ?? 0) ? 'menunggu' : null;

            // --- 2. EXECUTION ---
            return DB::transaction(function () use ($data, $statusPpns) {

                // A. Create Parent Record
                $penindakan = Penindakan::create([
                    'operasi_id'          => $data['operasi_id'] ?? null,
                    'pengaduan_id'        => $data['pengaduan_id'] ?? null,
                    'laporan_harian_id'   => $data['laporan_harian_id'] ?? null,
                    'anggota_pelapor_id'  => $data['anggota_pelapor_id'],
                    'jenis_penindakan'    => $data['jenis_penindakan'],
                    'uraian'              => $data['uraian'] ?? null,
                    'kecamatan_id'        => $data['kecamatan_id'] ?? null,
                    'desa_id'             => $data['desa_id'] ?? null,
                    'lokasi'              => $data['lokasi'] ?? null,
                    'lat'                 => $data['lat'] ?? null,
                    'lng'                 => $data['lng'] ?? null,
                    'butuh_validasi_ppns' => $data['butuh_validasi_ppns'] ?? false,
                    'status_validasi_ppns' => $statusPpns,
                    'created_by'          => Auth::id(),
                ]);

                if (!$penindakan) {
                    throw new CustomException('Gagal menambah penindakan', 422);
                }

                // B. Regulasi
                if (!empty($data['regulasi'])) {
                    $penindakan->penindakanRegulasi()->createMany($data['regulasi']);
                }

                // C. Anggota
                if (!empty($data['anggota'])) {
                    foreach ($data['anggota'] as $anggotaId) {
                        $penindakan->penindakanAnggota()->create([
                            'anggota_id' => $anggotaId,
                            'peran'      => $data['peran'][$anggotaId] ?? null,
                            'created_by' => Auth::id(),
                        ]);
                    }
                }

                // D. Lampiran (FOTO ONLY & OPTIMIZED)
                if (!empty($data['lampiran']) && is_array($data['lampiran'])) {
                    foreach ($data['lampiran'] as $item) {
                        // Ambil objek file dari array input
                        $file = $item['file'] ?? null;

                        if ($file instanceof UploadedFile) {
                            // 1. Optimize Image (Wajib Foto)
                            $path = $this->optimizeService->optimizeImage($file, 'penindakan');

                            // 2. Simpan ke DB
                            $lampiran = PenindakanLampiran::create([
                                'penindakan_id' => $penindakan->id,
                                'nama_file'     => $item['nama_file'] ?? $file->getClientOriginalName(),
                                'path_file'     => $path,
                                'jenis'         => 'foto', 
                                'created_by'    => Auth::id(),
                            ]);

                            // 3. Cleanup jika DB gagal
                            if (!$lampiran) {
                                Storage::disk('public')->delete($path);
                                throw new CustomException('Gagal menyimpan foto lampiran', 422);
                            }
                        }
                    }
                }

                return [
                    'success' => true,
                    'message' => 'Penindakan berhasil ditambahkan',
                    'data'    => $penindakan->load('penindakanRegulasi', 'penindakanLampiran', 'penindakanAnggota'),
                ];
            });
        } catch (\Exception $e) {
            Log::error('Gagal menambah penindakan', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new CustomException('Gagal menambah penindakan: ' . $e->getMessage(), 422);
        }
    }

    public function update($id, array $data): array
    {
        try {
            return DB::transaction(function () use ($id, $data) {
                $penindakan = Penindakan::findOrFail($id); // Langsung fail jika tidak ketemu

                // ... (Validasi Logic Permissions disini) ...

                // Update Parent
                $penindakan->update([
                    'uraian'           => $data['uraian'] ?? $penindakan->uraian,
                    'jenis_penindakan' => $data['jenis_penindakan'] ?? $penindakan->jenis_penindakan,
                    'lokasi'           => $data['lokasi'] ?? $penindakan->lokasi,
                    // ... field lain ...
                ]);

                // Update Regulasi & Anggota (Logic Delete-Insert atau Sync)
                // ...

                // UPDATE LAMPIRAN (FOTO ONLY)
                if (isset($data['lampiran']) && is_array($data['lampiran'])) {

                    // 1. Hapus lampiran yang dibuang user
                    $keptPaths = array_filter(array_column($data['lampiran'], 'path_file'));
                    $filesToDelete = $penindakan->penindakanLampiran()
                        ->whereNotIn('path_file', $keptPaths)
                        ->get();

                    foreach ($filesToDelete as $oldFile) {
                        Storage::disk('public')->delete($oldFile->path_file);
                        $oldFile->delete();
                    }

                    // 2. Tambah Lampiran Baru (Optimize)
                    foreach ($data['lampiran'] as $item) {
                        $file = $item['file'] ?? null;

                        if ($file instanceof UploadedFile) {
                            // Optimize & Simpan
                            $path = $this->optimizeService->optimizeImage($file, 'penindakan');

                            $penindakan->penindakanLampiran()->create([
                                'nama_file'  => $item['nama_file'] ?? $file->getClientOriginalName(),
                                'path_file'  => $path,
                                'jenis'      => 'foto', // Hardcode
                                'created_by' => Auth::id(),
                            ]);
                        }
                    }
                }

                return [
                    'success' => true,
                    'message' => 'Penindakan berhasil diperbarui',
                    'data'    => $penindakan->fresh()->load('penindakanLampiran'),
                ];
            });
        } catch (\Exception $e) {
            Log::error('Gagal update penindakan', ['error' => $e->getMessage()]);
            throw new CustomException('Gagal update penindakan', 422);
        }
    }

    public function validasiPPNS($id, array $data): array
    {
        try {
            return DB::transaction(function () use ($id, $data) {

                $penindakan = Penindakan::find($id);

                if (!$penindakan) {
                    throw new CustomException('Penindakan tidak ditemukan', 404);
                }

                if (!$penindakan->butuh_validasi_ppns) {
                    throw new CustomException('Penindakan ini tidak memerlukan validasi PPNS', 422);
                }

                if ($penindakan->status_validasi_ppns === 'ditolak' || $penindakan->status_validasi_ppns === 'disetujui') {
                    throw new CustomException('Validasi hanya dapat dilakukan ketika status masih menunggu atau revisi', 422);
                }

                if (!in_array($data['status_validasi_ppns'], ['disetujui', 'ditolak', 'revisi'])) {
                    throw new CustomException('Status valid harus disetujui/ditolak/revisi', 422);
                }

                $penindakan->update([
                    'status_validasi_ppns'  => $data['status_validasi_ppns'],
                    'catatan_validasi_ppns' => $data['catatan_validasi_ppns'] ?? null,
                    'ppns_validator_id'     => Auth::id(),
                    'tanggal_validasi_ppns' => now(),
                ]);


                // if ($data['status_validasi_ppns'] === 'disetujui') {
                //     app(BAPGeneratorService::class)->generate($penindakan);
                // }

                return [
                    'message' => 'Validasi PPNS berhasil',
                    'data'    => $penindakan,
                ];
            });
        } catch (Exception $e) {
            Log::error('Gagal melakukan validasi PPNS', ['error' => $e->getMessage()]);
            throw new CustomException('Gagal melakukan validasi PPNS', 422);
        }
    }
}

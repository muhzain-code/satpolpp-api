<?php

namespace App\Services\Penindakan;

use Exception;
use Illuminate\Support\Facades\DB;
use App\Exceptions\CustomException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\Penindakan\Penindakan;
use App\Services\BAPGeneratorService;
use Illuminate\Support\Facades\Storage;

class PenindakanService
{
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

    public function create(array $data): array
    {
        try {
            return DB::transaction(function () use ($data) {

                // Tentukan status PPNS awal
                $statusPpns = $data['jenis_penindakan'] === 'proses_hukum'
                    ? 'menunggu'
                    : null;

                $penindakan = Penindakan::create([
                    'operasi_id'         => $data['operasi_id'] ?? null,
                    'pengaduan_id'       => $data['pengaduan_id'] ?? null,
                    'laporan_harian_id'  => $data['laporan_harian_id'] ?? null,

                    'jenis_penindakan'   => $data['jenis_penindakan'],
                    'uraian'             => $data['uraian'] ?? null,

                    // Lokasi
                    'kecamatan_id'       => $data['kecamatan_id'] ?? null,
                    'desa_id'            => $data['desa_id'] ?? null,
                    'lokasi'             => $data['lokasi'] ?? null,
                    'lat'                => $data['lat'] ?? null,
                    'lng'                => $data['lng'] ?? null,

                    // PPNS
                    'butuh_validasi_ppns'   => $data['jenis_penindakan'] === 'proses_hukum',
                    'status_validasi_ppns'  => $statusPpns,

                    'created_by' => Auth::id(),
                ]);

                if (!$penindakan) {
                    throw new CustomException('Gagal menambah penindakan', 422);
                }

                // --- SIMPAN REGULASI ---
                if (!empty($data['regulasi'])) {
                    $penindakan->penindakanRegulasi()->createMany($data['regulasi']);
                }

                // --- SIMPAN LAMPIRAN ---
                if (!empty($data['lampiran'])) {
                    foreach ($data['lampiran'] as $file) {
                        $storedPath = $file->store('penindakan', 'public');

                        $penindakan->penindakanLampiran()->create([
                            'nama_file'  => $file->getClientOriginalName(),
                            'path_file'  => $storedPath,
                            'jenis'      => str_starts_with($file->getMimeType(), 'image') ? 'foto' : 'dokumen',
                            'created_by' => Auth::id(),
                        ]);
                    }
                }

                return [
                    'message' => 'Penindakan berhasil ditambahkan',
                    'data'    => $penindakan->load('penindakanRegulasi', 'penindakanLampiran'),
                ];
            });
        } catch (Exception $e) {
            Log::error('Gagal menambah penindakan', ['error' => $e->getMessage()]);
            throw new CustomException('Gagal menambah penindakan', 422);
        }
    }

    public function getById($id): array
    {
        $user = Auth::user();

        // Eager Load relasi yang diperlukan
        $query = Penindakan::with([
            'penindakanLampiran',
            'penindakanRegulasi.regulasi',
            'creator'
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

            // Lokasi (Flat)
            'kecamatan_id'          => $penindakan->kecamatan_id,
            'desa_id'               => $penindakan->desa_id,
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
        ];

        return [
            'message' => 'Detail penindakan berhasil ditampilkan',
            'data'    => $data
        ];
    }

    public function update($id, array $data): array
    {
        try {
            return DB::transaction(function () use ($id, $data) {

                $user = Auth::user();

                $penindakan = Penindakan::query()
                    ->when(
                        $user->hasRole('komandan_regu'),
                        fn($q) => $q->where('created_by', $user->id)
                    )
                    ->find($id);

                if (!$penindakan) {
                    throw new CustomException('Penindakan tidak ditemukan', 404);
                }

                // Bila sudah pakai regulasi, hanya boleh update jika status PPNS = revisi
                $isRegulasi = $penindakan->penindakanRegulasi()->exists();
                if ($isRegulasi && $penindakan->status_validasi_ppns !== 'revisi') {
                    throw new CustomException(
                        'Penindakan regulasi hanya bisa diubah ketika status PPNS = revisi',
                        403
                    );
                }

                $updateData = [
                    'uraian' => $data['uraian'] ?? $penindakan->uraian,

                    'jenis_penindakan' => $data['jenis_penindakan'] ?? $penindakan->jenis_penindakan,

                    // Lokasi update
                    'kecamatan_id' => $data['kecamatan_id'] ?? $penindakan->kecamatan_id,
                    'desa_id'      => $data['desa_id'] ?? $penindakan->desa_id,
                    'lokasi'       => $data['lokasi'] ?? $penindakan->lokasi,
                    'lat'          => $data['lat'] ?? $penindakan->lat,
                    'lng'          => $data['lng'] ?? $penindakan->lng,
                ];

                // Sumber kegiatan
                if (!empty($data['operasi_id'])) {
                    $updateData['operasi_id'] = $data['operasi_id'];
                    $updateData['pengaduan_id'] = null;
                    $updateData['laporan_harian_id'] = null;
                }

                if (!empty($data['pengaduan_id'])) {
                    $updateData['pengaduan_id'] = $data['pengaduan_id'];
                    $updateData['operasi_id'] = null;
                    $updateData['laporan_harian_id'] = null;
                }

                if (!empty($data['laporan_harian_id'])) {
                    $updateData['laporan_harian_id'] = $data['laporan_harian_id'];
                    $updateData['pengaduan_id'] = null;
                    $updateData['operasi_id'] = null;
                }

                // Reset status PPNS kalau diubah menjadi bukan proses hukum
                if (($data['jenis_penindakan'] ?? $penindakan->jenis_penindakan) !== 'proses_hukum') {
                    $updateData['butuh_validasi_ppns'] = false;
                    $updateData['status_validasi_ppns'] = null;
                    $updateData['catatan_validasi_ppns'] = null;
                    $updateData['ppns_validator_id'] = null;
                }

                $penindakan->update($updateData);

                // Update regulasi (hapus → create ulang)
                if (isset($data['regulasi'])) {
                    $penindakan->penindakanRegulasi()->delete();
                    $penindakan->penindakanRegulasi()->createMany($data['regulasi']);
                }

                // Tambah lampiran baru
                if (!empty($data['lampiran'])) {

                    // 1. Hapus lampiran lama kalau ada
                    if ($penindakan->penindakanLampiran()->exists()) {
                        foreach ($penindakan->penindakanLampiran as $old) {
                            if ($old->path_file && Storage::disk('public')->exists($old->path_file)) {
                                Storage::disk('public')->delete($old->path_file);
                            }
                            $old->delete();
                        }
                    }

                    // 2. Upload lampiran baru
                    foreach ($data['lampiran'] as $file) {
                        $storedPath = $file->store('penindakan', 'public');

                        $penindakan->lampiran()->create([
                            'nama_file'  => $file->getClientOriginalName(),
                            'path_file'  => $storedPath,
                            'jenis'      => str_starts_with($file->getMimeType(), 'image') ? 'foto' : 'dokumen',
                            'created_by' => Auth::id(),
                        ]);
                    }
                }

                return [
                    'message' => 'Penindakan berhasil diperbarui',
                    'data' => $penindakan->load('penindakanRegulasi', 'penindakanLampiran'),
                ];
            });
        } catch (Exception $e) {
            Log::error('Gagal memperbarui penindakan', ['error' => $e->getMessage()]);
            throw new CustomException('Gagal memperbarui penindakan', 422);
        }
    }


    public function delete($id): array
    {
        try {
            return DB::transaction(function () use ($id) {

                $penindakan = Penindakan::with('penindakanLampiran')->find($id);

                if (!$penindakan) {
                    throw new CustomException('Penindakan tidak ditemukan', 404);
                }

                $isRegulasi = $penindakan->penindakanRegulasi()->exists();

                if ($isRegulasi && $penindakan->status_validasi_ppns !== 'revisi') {
                    throw new CustomException(
                        'Penindakan regulasi hanya bisa dihapus jika status = revisi',
                        403
                    );
                }

                // Hapus file lampiran
                foreach ($penindakan->penindakanLampiran() as $lampiran) {
                    Storage::disk('public')->delete($lampiran->path_file);
                    $lampiran->delete();
                }

                $penindakan->penindakanRegulasi()->delete();
                $penindakan->delete();

                return ['message' => 'Penindakan berhasil dihapus'];
            });
        } catch (Exception $e) {
            throw new CustomException('Gagal menghapus penindakan', 422);
        }
    }

    public function validasiPPNS($id, array $data): array
    {
        try {
            return DB::transaction(function () use ($id, $data) {

                $penindakan = Penindakan::with('penindakanRegulasi')->find($id);

                if (!$penindakan) {
                    throw new CustomException('Penindakan tidak ditemukan', 404);
                }

                if ($penindakan->jenis_penindakan !== 'proses_hukum') {
                    throw new CustomException('Penindakan ini tidak memerlukan validasi PPNS', 422);
                }

                if ($penindakan->status_validasi_ppns !== 'menunggu') {
                    throw new CustomException('Validasi hanya dapat dilakukan ketika status masih menunggu', 422);
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

                if ($data['status_validasi_ppns'] === 'disetujui') {
                    app(BAPGeneratorService::class)->generate($penindakan);
                }

                return [
                    'message' => 'Validasi PPNS berhasil',
                    'data'    => $penindakan->load('penindakanRegulasi', 'penindakanLampiran'),
                ];
            });
        } catch (Exception $e) {
            throw new CustomException('Gagal melakukan validasi PPNS', 422);
        }
    }
}

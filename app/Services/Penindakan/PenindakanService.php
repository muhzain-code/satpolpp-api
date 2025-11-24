<?php

namespace App\Services\Penindakan;

use Exception;
use App\Models\Operasi\Operasi;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use App\Exceptions\CustomException;
use App\Models\Pengaduan\Pengaduan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\Penindakan\Penindakan;
use App\Services\BAPGeneratorService;
use Illuminate\Support\Facades\Storage;
use App\Models\Penindakan\PenindakanLampiran;
use App\Models\Penindakan\PenindakanRegulasi;

class PenindakanService
{
    public function getAll($filter): array
    {
        $user = Auth::user();

        if ($user->hasRole('super_admin') ||  $user->hasRole('ppns')) {
            $query = Penindakan::with(['operasi:id,nama', 'pengaduan:id,nomor_tiket']);
        }

        if ($user->hasRole('komandan_regu')) {
            $query = Penindakan::with(['operasi:id,nama', 'pengaduan:id,nomor_tiket'])->where('anggota_pelapor_id', $user->anggota?->id);
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

                $hasRegulasi = !empty($data['regulasi']);

                $statusPpns = $hasRegulasi ? 'menunggu' : 'disetujui';

                $penindakan = Penindakan::create([
                    'operasi_id'  => $data['operasi_id'] ?? null,
                    'laporan_harian_id'  => $data['laporan_harian_id'] ?? null,
                    'pengaduan_id' => $data['pengaduan_id'] ?? null,
                    'uraian'      => $data['uraian'] ?? null,
                    'denda'       => $data['denda'] ?? 0,
                    'status_validasi_ppns'  => $statusPpns,
                    'created_by' => Auth::id(),
                ]);

                if (!$penindakan) {
                    throw new CustomException('Gagal menambah penindakan', 422);
                }

                // --- SIMPAN REGULASI ---
                if ($hasRegulasi) {
                    $penindakan->regulasi()->createMany($data['regulasi']);
                }

                // --- SIMPAN LAMPIRAN ---
                if (!empty($data['lampiran'])) {
                    foreach ($data['lampiran'] as $file) {
                        $storedPath = $file->store('penindakan', 'public');

                        $penindakan->lampiran()->create([
                            'nama_file' => $file->getClientOriginalName(),
                            'path_file' => $storedPath,
                            'jenis'     => str_starts_with($file->getMimeType(), 'image')
                                ? 'foto'
                                : 'dokumen',
                            'created_by' => Auth::id(),
                        ]);
                    }
                }

                return [
                    'message' => 'Penindakan berhasil ditambahkan',
                    'data'    => $penindakan->load('regulasi', 'lampiran'),
                ];
            });
        } catch (Exception $e) {
            Log::error('Gagal menambah penindakan', ['error' => $e->getMessage()]);
            throw new CustomException('Gagal menambah penindakan', 422);
        }
    }

    public function getById($id): array
    {
        $user  = Auth::user();

        if ($user->hasRole('super_admin') ||  $user->hasRole('ppns')) {
            $penindakan = Penindakan::with('regulasi', 'lampiran')->find($id);
        }

        if ($user->hasRole('komandan_regu')) {
            $penindakan = Penindakan::with('regulasi', 'lampiran')
                ->where('created_by', $user->id)
                ->find($id);
        }

        if (!$penindakan) {
            throw new CustomException('Penindakan tidak ditemukan', 404);
        }

        return [
            'message' => 'Detail penindakan berhasil ditampilkan',
            'data' => $penindakan
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

                $isRegulasi = $penindakan->regulasi()->exists();

                if ($isRegulasi && $penindakan->status_validasi_ppns !== 'revisi') {
                    throw new CustomException(
                        'Penindakan regulasi hanya bisa diubah ketika status PPNS = revisi',
                        403
                    );
                }

                // --- HANDLE MUTUAL SOURCE ---
                $updateData = [
                    'uraian' => $data['uraian'] ?? $penindakan->uraian,
                    'denda'  => $data['denda'] ?? $penindakan->denda,
                ];

                if (!empty($data['operasi_id'])) {
                    $updateData['operasi_id'] = $data['operasi_id'];
                    $updateData['pengaduan_id'] = null;
                }

                if (!empty($data['pengaduan_id'])) {
                    $updateData['pengaduan_id'] = $data['pengaduan_id'];
                    $updateData['operasi_id'] = null;
                }

                $penindakan->update($updateData);

                // --- UPDATE REGULASI (RESET ULANG) ---
                if (isset($data['regulasi'])) {
                    $penindakan->regulasi()->delete();
                    $penindakan->regulasi()->createMany($data['regulasi']);
                }

                // --- LAMPIRAN BARU ---
                if (!empty($data['lampiran'])) {
                    foreach ($data['lampiran'] as $file) {
                        $storedPath = $file->store('penindakan', 'public');

                        $penindakan->lampiran()->create([
                            'nama_file' => $file->getClientOriginalName(),
                            'path_file' => $storedPath,
                            'jenis'     => str_starts_with($file->getMimeType(), 'image')
                                ? 'foto'
                                : 'dokumen',
                            'created_by' => Auth::id(),
                        ]);
                    }
                }

                return [
                    'message' => 'Penindakan berhasil diperbarui',
                    'data' => $penindakan->load('regulasi', 'lampiran'),
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

                $penindakan = Penindakan::with('lampiran')->find($id);

                if (!$penindakan) {
                    throw new CustomException('Penindakan tidak ditemukan', 404);
                }

                $isRegulasi = $penindakan->regulasi()->exists();

                if ($isRegulasi && $penindakan->status_validasi_ppns !== 'revisi') {
                    throw new CustomException(
                        'Penindakan regulasi hanya bisa dihapus jika status = revisi',
                        403
                    );
                }

                // Hapus file lampiran
                foreach ($penindakan->lampiran as $lampiran) {
                    Storage::disk('public')->delete($lampiran->path_file);
                    $lampiran->delete();
                }

                $penindakan->regulasi()->delete();
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

                $penindakan = Penindakan::with('regulasi')->find($id);

                if (!$penindakan) {
                    throw new CustomException('Penindakan tidak ditemukan', 404);
                }

                if (!$penindakan->regulasi()->exists()) {
                    throw new CustomException('Penindakan ini bukan regulasi dan tidak memerlukan validasi PPNS', 422);
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
                ]);

                if ($data['status_validasi_ppns'] === 'disetujui') {
                    app(BAPGeneratorService::class)->generate($penindakan);
                }

                return [
                    'message' => 'Validasi PPNS berhasil',
                    'data'    => $penindakan->load('regulasi', 'lampiran'),
                ];
            });
        } catch (Exception $e) {
            throw new CustomException('Gagal melakukan validasi PPNS', 422);
        }
    }
}

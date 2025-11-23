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
                // Jika sumber OPERASI → status_validasi otomatis menunggu
                if (!empty($data['operasi_id'])) {
                    $data['status_validasi_ppns'] = 'menunggu';
                }

                $penindakan = Penindakan::create([
                    'operasi_id'         => $data['operasi_id'] ?? null,
                    'pengaduan_id'       => $data['pengaduan_id'] ?? null,
                    'uraian'             => $data['uraian'] ?? null,
                    'denda'              => $data['denda'] ?? null,
                    'status_validasi_ppns'  => 'menunggu',
                    'catatan_validasi_ppns' => $data['catatan_validasi_ppns'] ?? null,
                    'ppns_validator_id'     => $data['ppns_validator_id'] ?? null,
                    'created_by'        => Auth::id(),
                ]);

                if (!$penindakan) {
                    throw new CustomException('Gagal menambah penindakan', 422);
                }

                // Simpan regulasi
                if (!empty($data['regulasi'])) {
                    $penindakan->regulasi()->createMany($data['regulasi']);
                }

                // Simpan lampiran
                if (!empty($data['lampiran'])) {
                    foreach ($data['lampiran'] as $file) {
                        $storedPath = $file->store('penindakan', 'public');
                        $penindakan->lampiran()->create([
                            'nama_file' => $file->getClientOriginalName(),
                            'path_file' => $storedPath,
                            'jenis'     => explode('/', $file->getMimeType())[0] === 'image' ? 'foto' : 'dokumen',
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

                // Ambil data sesuai role
                if ($user->hasRole('super_admin')) {
                    $penindakan = Penindakan::find($id);
                } elseif ($user->hasRole('komandan_regu')) {
                    $penindakan = Penindakan::where('created_by', $user->id)->find($id);
                }

                if (!$penindakan) {
                    throw new CustomException('Penindakan tidak ditemukan', 404);
                }

                // 🚫 --- Batasi update hanya jika status_validasi_ppns = revisi ---
                if ($penindakan->status_validasi_ppns !== 'revisi') {
                    throw new CustomException(
                        'Penindakan tidak dapat diperbarui karena belum direvisi oleh PPNS',
                        403
                    );
                }

                // --- 🔥 HANDLE OPERASI_ID & PENGADUAN_ID (mutually exclusive) ---
                $updateData = [];

                if (!empty($data['operasi_id'])) {
                    $updateData['operasi_id'] = $data['operasi_id'];
                    $updateData['pengaduan_id'] = null;
                }

                if (!empty($data['pengaduan_id'])) {
                    $updateData['pengaduan_id'] = $data['pengaduan_id'];
                    $updateData['operasi_id'] = null;
                }

                // Update field lain
                $updateData['uraian'] = $data['uraian'] ?? $penindakan->uraian;
                $updateData['denda'] = $data['denda'] ?? $penindakan->denda;

                $penindakan->update($updateData);

                // --- Update Regulasi ---
                if (isset($data['regulasi'])) {
                    PenindakanRegulasi::where('penindakan_id', $id)->delete();
                    $penindakan->regulasi()->createMany($data['regulasi']);
                }

                // --- Tambah Lampiran Baru ---
                if (!empty($data['lampiran'])) {
                    $penindakan->lampiran()->createMany($data['lampiran']);
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

    // public function update($id, array $data): array
    // {
    //     try {
    //         return DB::transaction(function () use ($id, $data) {
    //             $user = Auth::user();

    //             // Ambil data sesuai role
    //             if ($user->hasRole('super_admin')) {
    //                 $penindakan = Penindakan::find($id);
    //             } elseif ($user->hasRole('komandan_regu')) {
    //                 $penindakan = Penindakan::where('created_by', $user->id)->find($id);
    //             }

    //             if (!$penindakan) {
    //                 throw new CustomException('Penindakan tidak ditemukan', 404);
    //             }

    //             // --- 🔥 HANDLE OPERASI_ID & PENGADUAN_ID (mutually exclusive) ---
    //             $updateData = [];

    //             if (array_key_exists('operasi_id', $data) && !empty($data['operasi_id'])) {
    //                 $updateData['operasi_id'] = $data['operasi_id'];
    //                 $updateData['pengaduan_id'] = null; // wajib null
    //             }

    //             if (array_key_exists('pengaduan_id', $data) && !empty($data['pengaduan_id'])) {
    //                 $updateData['pengaduan_id'] = $data['pengaduan_id'];
    //                 $updateData['operasi_id'] = null; // wajib null
    //             }

    //             // Update field lain
    //             $updateData['uraian'] = $data['uraian'] ?? $penindakan->uraian;
    //             $updateData['denda'] = $data['denda'] ?? $penindakan->denda;

    //             $penindakan->update($updateData);

    //             // --- Update Regulasi ---
    //             if (isset($data['regulasi'])) {
    //                 PenindakanRegulasi::where('penindakan_id', $id)->delete();
    //                 $penindakan->regulasi()->createMany($data['regulasi']);
    //             }

    //             // --- Tambah Lampiran Baru ---
    //             if (!empty($data['lampiran'])) {
    //                 $penindakan->lampiran()->createMany($data['lampiran']);
    //             }

    //             return [
    //                 'message' => 'Penindakan berhasil diperbarui',
    //                 'data' => $penindakan->load('regulasi', 'lampiran'),
    //             ];
    //         });
    //     } catch (Exception $e) {
    //         Log::error('Gagal memperbarui penindakan', ['error' => $e->getMessage()]);
    //         throw new CustomException('Gagal memperbarui penindakan', 422);
    //     }
    // }


    // public function update($id, array $data): array
    // {
    //     try {
    //         return DB::transaction(function () use ($id, $data) {
    //             $user = Auth::user();

    //             if ($user->hasRole('super_admin')) {
    //                 $penindakan = Penindakan::find($id);
    //             } 

    //             if ($user->hasRole('komandan_regu')) {
    //                 $penindakan = Penindakan::where('created_by', $user->id)->find($id);
    //             }

    //             if (!$penindakan) {
    //                 throw new CustomException('Penindakan tidak ditemukan', 404);
    //             }

    //             $penindakan->update([
    //                 'uraian' => $data['uraian'] ?? $penindakan->uraian,
    //                 'denda' => $data['denda'] ?? $penindakan->denda,
    //             ]);

    //             if (isset($data['regulasi'])) {
    //                 PenindakanRegulasi::where('penindakan_id', $id)->delete();
    //                 foreach ($data['regulasi'] as $item) {
    //                     PenindakanRegulasi::create([
    //                         'penindakan_id' => $id,
    //                         'regulasi_id' => $item['regulasi_id'],
    //                         'pasal_dilanggar' => $item['pasal_dilanggar'] ?? null,
    //                     ]);
    //                 }
    //             }

    //             if (!empty($data['lampiran'])) {
    //                 foreach ($data['lampiran'] as $file) {
    //                     PenindakanLampiran::create([
    //                         'penindakan_id' => $id,
    //                         'nama_file' => $file['nama_file'] ?? null,
    //                         'path_file' => $file['path_file'],
    //                         'jenis' => $file['jenis'] ?? null,
    //                     ]);
    //                 }
    //             }

    //             return [
    //                 'message' => 'Penindakan berhasil diperbarui',
    //                 'data' => $penindakan->load('regulasi', 'lampiran')
    //             ];
    //         });
    //     } catch (Exception $e) {
    //         Log::error('Gagal memperbarui penindakan', ['error' => $e->getMessage()]);
    //         throw new CustomException('Gagal memperbarui penindakan', 422);
    //     }
    // }

    public function delete($id): array
    {
        try {
            return DB::transaction(function () use ($id) {
                $user = Auth::user();

                if ($user->hasRole('super_admin')) {
                    $penindakan = Penindakan::with('lampiran')->find($id);
                }

                if ($user->hasRole('komandan_regu')) {
                    $penindakan = Penindakan::with('lampiran')
                        ->where('created_by', $user->id)
                        ->find($id);
                }

                if (!$penindakan) {
                    throw new CustomException('Penindakan tidak ditemukan', 404);
                }

                foreach ($penindakan->lampiran as $lampiran) {
                    Storage::disk('public')->delete($lampiran->path_file);
                    $lampiran->delete();
                }

                PenindakanRegulasi::where('penindakan_id', $id)->delete();
                $penindakan->delete();

                return [
                    'message' => 'Penindakan berhasil dihapus',
                    'data' => true
                ];
            });
        } catch (Exception $e) {
            Log::error('Gagal menghapus penindakan', ['error' => $e->getMessage()]);
            throw new CustomException('Gagal menghapus penindakan', 422);
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

                if ($penindakan->status_validasi_ppns !== 'menunggu') {
                    throw new CustomException('Validasi hanya dapat dilakukan ketika status masih menunggu', 422);
                }

                if (!in_array($data['status_validasi_ppns'], ['disetujui', 'ditolak'])) {
                    throw new CustomException('Status validasi hanya boleh disetujui atau ditolak', 422);
                }

                $ppnsId = Auth::id();
                if (!$ppnsId) {
                    throw new CustomException('Akun PPNS tidak terautentikasi', 401);
                }

                // 🔥 Update validasi PPNS
                $penindakan->update([
                    'status_validasi_ppns'   => $data['status_validasi_ppns'],
                    'catatan_validasi_ppns'  => $data['catatan_validasi_ppns'] ?? null,
                    'ppns_validator_id'      => $ppnsId,
                ]);

                // Jika disetujui → buat BAP
                if ($data['status_validasi_ppns'] === 'disetujui') {
                    app(\App\Services\BAPGeneratorService::class)->generate($penindakan->load('regulasi', 'lampiran'));
                }

                // Update status pengaduan jika ada
                if ($penindakan->pengaduan_id) {
                    Pengaduan::where('id', $penindakan->pengaduan_id)
                        ->update(['status' => 'selesai', 'selesai_at' => now()]);
                }

                // Atau update dari operasi
                if ($penindakan->operasi_id && !$penindakan->pengaduan_id) {
                    $operasi = Operasi::with('pengaduan:id,operasi_id,status')
                        ->find($penindakan->operasi_id);

                    if ($operasi && $operasi->pengaduan) {
                        Pengaduan::where('id', $operasi->pengaduan->id)
                            ->update(['status' => 'selesai', 'selesai_at' => now()]);
                    }
                }

                return [
                    'message' => 'Validasi PPNS berhasil diproses',
                    'data' => $penindakan->load('regulasi', 'lampiran'),
                ];
            });
        } catch (Exception $e) {

            Log::error('Gagal melakukan validasi PPNS', [
                'penindakan_id' => $id,
                'error'         => $e->getMessage()
            ]);

            throw new CustomException('Gagal melakukan validasi PPNS', 422);
        }
    }
}

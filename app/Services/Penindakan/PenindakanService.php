<?php

namespace App\Services\Penindakan;

use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use App\Exceptions\CustomException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\Penindakan\Penindakan;
use Illuminate\Support\Facades\Storage;
use App\Models\Penindakan\PenindakanLampiran;
use App\Models\Penindakan\PenindakanRegulasi;
use App\Models\ManajemenLaporan\LaporanHarian;

class PenindakanService
{
    public function getAll($filter): array
    {
        $user = Auth::user();

        if ($user->hasRole('super_admin') || $user->hasRole('komandan_regu') || $user->hasRole('ppns')) {
            $query = Penindakan::with(['operasi:id,nama', 'pengaduan:id,nomor_tiket', 'laporanHarian:id,judul']);
        }

        if ($user->hasRole('anggota_regu')) {
            $query = Penindakan::with(['operasi:id,nama', 'pengaduan:id,nomor_tiket', 'laporanHarian:id,judul'])->where('anggota_pelapor_id', $user->anggota?->id);
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

                // Ambil pelapor dari laporan harian bila sumber laporan_harian
                if (!empty($data['laporan_harian_id'])) {
                    $laporan = LaporanHarian::find($data['laporan_harian_id']);
                    $data['anggota_pelapor_id'] = $laporan?->anggota_id;
                }

                // Jika sumber OPERASI → status_validasi otomatis menunggu
                if (!empty($data['operasi_id'])) {
                    $data['status_validasi_ppns'] = 'menunggu';
                }

                $penindakan = Penindakan::create([
                    'operasi_id'         => $data['operasi_id'] ?? null,
                    'pengaduan_id'       => $data['pengaduan_id'] ?? null,
                    'laporan_harian_id'  => $data['laporan_harian_id'] ?? null,
                    'anggota_pelapor_id' => $data['anggota_pelapor_id'],
                    'uraian'             => $data['uraian'] ?? null,
                    'barang_bukti'       => $data['barang_bukti'] ?? null,
                    'denda'              => $data['denda'] ?? null,
                    'status_validasi_ppns'  => $data['status_validasi_ppns'],
                    'catatan_validasi_ppns' => $data['catatan_validasi_ppns'] ?? null,
                    'ppns_validator_id'     => $data['ppns_validator_id'] ?? null,
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

        if ($user->hasRole('super_admin') || $user->hasRole('komandan_regu') || $user->hasRole('ppns')) {
            $penindakan = Penindakan::with('regulasi', 'lampiran')->find($id);
        } else if ($user->hasRole('anggota_regu')) {
            $penindakan = Penindakan::with('regulasi', 'lampiran')
                ->where('anggota_pelapor_id', $user->anggota?->id)
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

                if ($user->hasRole('super_admin')) {
                    $penindakan = Penindakan::find($id);
                } else if ($user->hasRole('anggota_regu')) {
                    $penindakan = Penindakan::where('anggota_pelapor_id', $user->anggota?->id)->find($id);
                }

                if (!$penindakan) {
                    throw new CustomException('Penindakan tidak ditemukan', 404);
                }

                $penindakan->update([
                    'anggota_pelapor_id' => $data['anggota_pelapor_id'] ?? $penindakan->anggota_pelapor_id,
                    'uraian' => $data['uraian'] ?? $penindakan->uraian,
                    'barang_bukti' => $data['barang_bukti'] ?? $penindakan->barang_bukti,
                    'denda' => $data['denda'] ?? $penindakan->denda,
                    'status_validasi_ppns' => $data['status_validasi_ppns'] ?? $penindakan->status_validasi_ppns,
                    'catatan_validasi_ppns' => $data['catatan_validasi_ppns'] ?? $penindakan->catatan_validasi_ppns,
                    'ppns_validator_id' => $data['ppns_validator_id'] ?? $penindakan->ppns_validator_id,
                ]);

                if (isset($data['regulasi'])) {
                    PenindakanRegulasi::where('penindakan_id', $id)->delete();
                    foreach ($data['regulasi'] as $item) {
                        PenindakanRegulasi::create([
                            'penindakan_id' => $id,
                            'regulasi_id' => $item['regulasi_id'],
                            'pasal_dilanggar' => $item['pasal_dilanggar'] ?? null,
                        ]);
                    }
                }

                if (!empty($data['lampiran'])) {
                    foreach ($data['lampiran'] as $file) {
                        PenindakanLampiran::create([
                            'penindakan_id' => $id,
                            'nama_file' => $file['nama_file'] ?? null,
                            'path_file' => $file['path_file'],
                            'jenis' => $file['jenis'] ?? null,
                        ]);
                    }
                }

                return [
                    'message' => 'Penindakan berhasil diperbarui',
                    'data' => $penindakan->load('regulasi', 'lampiran')
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
                $user = Auth::user();

                if ($user->hasRole('super_admin')) {
                    $penindakan = Penindakan::with('lampiran')->find($id);
                } else if ($user->hasRole('anggota_regu')) {
                    $penindakan = Penindakan::with('lampiran')
                        ->where('anggota_pelapor_id', $user->anggota?->id)
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

                $penindakan->update([
                    'status_validasi_ppns' => $data['status_validasi_ppns'],
                    'catatan_validasi_ppns' => $data['catatan_validasi_ppns'] ?? null,
                    'ppns_validator_id' => $ppnsId,
                ]);

                return [
                    'message' => 'Validasi PPNS berhasil diproses',
                    'data' => $penindakan->load('regulasi', 'lampiran'),
                ];
            });
        } catch (Exception $e) {
            Log::error('Gagal melakukan validasi PPNS', [
                'penindakan_id' => $id,
                'error' => $e->getMessage()
            ]);

            throw new CustomException('Gagal melakukan validasi PPNS', 422);
        }
    }
}

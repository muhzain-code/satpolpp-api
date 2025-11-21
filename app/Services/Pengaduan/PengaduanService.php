<?php

namespace App\Services\Pengaduan;

use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use App\Exceptions\CustomException;
use App\Models\Pengaduan\Pengaduan;
use Illuminate\Support\Facades\Log;
use App\Services\NomorGeneratorService;
use Illuminate\Support\Facades\Storage;
use App\Models\Pengaduan\PengaduanLampiran;

class PengaduanService
{
    protected NomorGeneratorService $service;

    public function __construct(NomorGeneratorService $service)
    {
        $this->service = $service;
    }

    public function getAll($filter): array
    {
        $pengaduan = Pengaduan::with('kategoriPengaduan:id,nama');

        if (isset($filter['status'])) {
            $pengaduan->where('status', strtolower($filter['status']));
        }

        if (isset($filter['kategori_id'])) {
            $pengaduan->where('kategori_id', $filter['kategori_id']);
        }

        $pengaduan = $pengaduan->paginate($filter['per_page'], ['*'], 'page', $filter['page']);

        $pengaduan->getCollection()->transform(function ($item) {
            return [
                'id' => $item->id,
                'nomor_tiket' => $item->nomor_tiket,
                'nama_pelapor' => $item->nama_pelapor,
                'kontak_pelapor' => $item->kontak_pelapor,
                'kategori' => $item->kategoriPengaduan->nama,
                'status' => $item->status,
            ];
        });

        return [
            'message' => 'Pengaduan berhasil ditampilkan',
            'data' => [
                'current_page' => $pengaduan->currentPage(),
                'per_page' => $pengaduan->perPage(),
                'total' => $pengaduan->total(),
                'last_page' => $pengaduan->lastPage(),
                'items' => $pengaduan->items()
            ]
        ];
    }

    public function create(array $data): array
    {
        try {
            return DB::transaction(function () use ($data) {
                $nomorTiket = $this->service->generateNomorTiket();

                $pengaduan = Pengaduan::create([
                    'nomor_tiket' => $nomorTiket,
                    'nama_pelapor' => $data['nama_pelapor'],
                    'kontak_pelapor' => $data['kontak_pelapor'],
                    'kategori_id' => $data['kategori_id'] ?? null,
                    'deskripsi' => $data['deskripsi'],
                    'lat' => $data['lat'] ?? null,
                    'lng' => $data['lng'] ?? null,
                    'alamat' => $data['alamat'] ?? null,
                    'status' => 'diterima',
                    'diterima_at' => now(),
                ]);

                if (!$pengaduan) {
                    throw new CustomException('Gagal menambah pengaduan', 422);
                }

                if (!empty($data['lampiran']) && is_array($data['lampiran'])) {
                    foreach ($data['lampiran'] as $file) {
                        if ($file instanceof UploadedFile) {
                            $originalName = $file->getClientOriginalName();
                            $path = $file->store('pengaduan', 'public');

                            $pengaduanLampiran = PengaduanLampiran::create([
                                'pengaduan_id' => $pengaduan->id,
                                'nama_file' => $originalName,
                                'path_file' => $path,
                            ]);

                            if (!$pengaduanLampiran) {
                                Storage::disk('public')->delete($path);
                                throw new CustomException('Gagal menambah pengaduan lampiran', 422);
                            }
                        }
                    }
                }

                return [
                    'success' => true,
                    'message' => 'Pengaduan berhasil ditambahkan',
                    'data' => $pengaduan->load('pengaduanLampiran'),
                ];
            });
        } catch (Exception $e) {
            Log::error('Gagal menambah pengaduan', [
                'error' => $e->getMessage(),
            ]);

            throw new CustomException('Gagal menambah pengaduan', 422);
        }
    }

    public function getById($id): array
    {
        $pengaduan = Pengaduan::with('pengaduanLampiran', 'kategoriPengaduan:id,nama')->find($id);

        if (!$pengaduan) {
            throw new CustomException('Pengaduan tidak ditemukan', 404);
        }

        return [
            'message' => 'Detail pengaduan berhasil ditampilkan',
            'data' => $pengaduan
        ];
    }

    public function update($id, array $data): array
    {
        try {
            return DB::transaction(function () use ($id, $data) {
                $pengaduan = Pengaduan::find($id);

                if (!$pengaduan) {
                    throw new CustomException('Pengaduan tidak ditemukan', 404);
                }

                $pengaduan->update([
                    'nama_pelapor' => $data['nama_pelapor'] ?? $pengaduan->nama_pelapor,
                    'kontak_pelapor' => $data['kontak_pelapor'] ?? $pengaduan->kontak_pelapor,
                    'kategori_id' => $data['kategori_id'] ?? $pengaduan->kategori_id,
                    'deskripsi' => $data['deskripsi'] ?? $pengaduan->deskripsi,
                    'lat' => $data['lat'] ?? $pengaduan->lat,
                    'lng' => $data['lng'] ?? $pengaduan->lng,
                    'alamat' => $data['alamat'] ?? $pengaduan->alamat,
                ]);

                if (!empty($data['lampiran']) && is_array($data['lampiran'])) {
                    foreach ($data['lampiran'] as $file) {
                        if ($file instanceof UploadedFile) {
                            $originalName = $file->getClientOriginalName();
                            $path = $file->store('pengaduan', 'public');

                            $lampiran = PengaduanLampiran::create([
                                'pengaduan_id' => $pengaduan->id,
                                'nama_file' => $originalName,
                                'path_file' => $path,
                            ]);

                            if (!$lampiran) {
                                Storage::disk('public')->delete($path);
                                throw new CustomException('Gagal menambah pengaduan lampiran', 422);
                            }
                        }
                    }
                }

                return [
                    'message' => 'Pengaduan berhasil diperbarui',
                    'data' => $pengaduan->load('pengaduanLampiran')
                ];
            });
        } catch (Exception $e) {
            Log::error('Gagal memperbarui pengaduan', [
                'error' => $e->getMessage(),
            ]);

            throw new CustomException('Gagal memperbarui pengaduan', 422);
        }
    }

    public function delete($id): array
    {
        try {
            return DB::transaction(function () use ($id) {
                $pengaduan = Pengaduan::with('pengaduanLampiran')->find($id);

                if (!$pengaduan) {
                    throw new CustomException('Pengaduan tidak ditemukan', 404);
                }

                foreach ($pengaduan->pengaduanLampiran as $lampiran) {
                    Storage::disk('public')->delete($lampiran->path_file);
                    $lampiran->delete();
                }

                $pengaduan->delete();

                return [
                    'message' => 'Pengaduan berhasil dihapus',
                    'data' => true
                ];
            });
        } catch (Exception $e) {
            Log::error('Gagal menghapus pengaduan', [
                'error' => $e->getMessage(),
            ]);

            throw new CustomException('Gagal menghapus pengaduan', 422);
        }
    }

    public function setDitolak($id): array
    {
        $pengaduan = Pengaduan::find($id);
        if (!$pengaduan) {
            throw new CustomException('Pengaduan tidak ditemukan', 404);
        }

        if ($pengaduan->status !== 'diterima') {
            throw new CustomException("Pengaduan hanya bisa ditolak dari status diterima", 422);
        }

        $pengaduan->update([
            'status' => 'ditolak',
        ]);

        return [
            'message' => 'Pengaduan telah ditolak',
            'data' => $pengaduan
        ];
    }

    public function lacakNomorTiket($nomor)
    {
        $pengaduan = Pengaduan::with('kategoriPengaduan:id,nama')->where('nomor_tiket', $nomor)->first();

        if (! $pengaduan) {
            throw new CustomException('Data pengaduan tidak ditemukan', 404);
        }

        $data = [
            'id' => $pengaduan->id,
            'nomor_tiket' => $pengaduan->nomor_tiket,
            'nama_pelapor' => $pengaduan->nama_pelapor,
            'kontak_pelapor' => $pengaduan->kontak_pelapor,
            'kategori' => $pengaduan->kategoriPengaduan->nama ?? null,
            'status' => $pengaduan->status,
        ];

        return [
            'message' => 'Data pengaduan berhasil ditemukan',
            'data' => $data
        ];
    }
}

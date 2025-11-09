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
                'deskripsi' => $item->deskripsi,
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
}

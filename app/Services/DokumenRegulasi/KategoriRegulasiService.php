<?php

namespace App\Services\DokumenRegulasi;

use App\Exceptions\CustomException;
use App\Models\DokumenRegulasi\KategoriRegulasi;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class KategoriRegulasiService
{
    public function getall($filters, $perPage, $currentPage): array
    {
        $query = KategoriRegulasi::query();

        if (!empty($filters['keyword'])) {
            $keyword = $filters['keyword'];
            $query->where('nama', 'like', "%{$keyword}%")
                ->orWhere('keterangan', 'like', "%{$keyword}%");
        }

        $kategori = $query->orderBy('nama')
            ->paginate($perPage, ['*'], 'page', $currentPage);

        $items = $kategori->getCollection()->transform(function ($item) {
            return [
                'id' => $item->id,
                'nama' => $item->nama,
                'keterangan' => $item->keterangan,
            ];
        });

        return [
            'message' => 'Data berhasil diambil',
            'data' => [
                'current_page' => $kategori->currentPage(),
                'per_page' => $kategori->perPage(),
                'total' => $kategori->total(),
                'last_page' => $kategori->lastPage(),
                'items' => $items,
            ]
        ];
    }

    public function create(array $data): array
    {
        try {
            $data['created_by'] = Auth::id(); // tambahkan created_by

            $kategori = KategoriRegulasi::create([
                'nama' => $data['nama'],
                'keterangan' => $data['keterangan'] ?? null,
                'created_by' => $data['created_by'],
            ]);

            return [
                'message' => 'Data berhasil ditambahkan',
                'data' => $kategori
            ];
        } catch (\Throwable $e) {
            Log::error('Gagal menambah kategori regulasi', ['message' => $e->getMessage()]);
            throw new CustomException('Gagal menambah kategori regulasi', 422);
        }
    }

    // Update
    public function update($id, array $data): array
    {
        try {
            $data['updated_by'] = Auth::id();

            $kategori = KategoriRegulasi::find($id);
            if (!$kategori) {
                throw new CustomException('Kategori regulasi tidak ditemukan', 404);
            }

            $kategori->update([
                'nama' => $data['nama'],
                'keterangan' => $data['keterangan'] ?? $kategori->keterangan,
                'updated_by' => $data['updated_by'],
            ]);

            return [
                'message' => 'Data kategori regulasi berhasil diperbarui',
                'data' => $kategori
            ];
        } catch (\Throwable $e) {
            Log::error('Gagal update kategori regulasi', ['error' => $e->getMessage()]);
            throw new CustomException('Gagal memperbarui kategori regulasi', 422);
        }
    }

    public function getbyId($id): array
    {
        $kategori = KategoriRegulasi::find($id);
        if (!$kategori) {
            throw new CustomException('Kategori regulasi tidak ditemukan', 404);
        }

        return [
            'message' => 'Data berhasil ditampilkan',
            'data' => [
                'id' => $kategori->id,
                'nama' => $kategori->nama,
                'keterangan' => $kategori->keterangan,
            ]
        ];
    }

    public function delete($id): array
    {
        $kategori = KategoriRegulasi::find($id);
        if (!$kategori) {
            throw new CustomException('Kategori regulasi tidak ditemukan', 422);
        }

        $kategori->delete();

        return [
            'message' => 'Data kategori regulasi berhasil dihapus'
        ];
    }
}

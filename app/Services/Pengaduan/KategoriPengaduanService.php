<?php

namespace App\Services\Pengaduan;

use App\Exceptions\CustomException;
use App\Models\Pengaduan\KategoriPengaduan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class KategoriPengaduanService
{
    public function getAll($perPage, $currentPage): array
    {
        $kategori = KategoriPengaduan::orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $currentPage);

        return [
            'message' => 'Data kategori pengaduan berhasil diambil',
            'data' => [
                'current_page' => $kategori->currentPage(),
                'per_page' => $kategori->perPage(),
                'total' => $kategori->total(),
                'last_page' => $kategori->lastPage(),
                'items' => $kategori->items()
            ]
        ];
    }

    public function getById($id)
    {
        $kategori = KategoriPengaduan::find($id);

        if (!$kategori) {
            throw new CustomException('Data kategori pengaduan tidak ditemukan', 404);
        }

        return [
            'message' => 'Data kategori pengaduan berhasil ditemukan',
            'data' => $kategori
        ];
    }

    public function create(array $data)
    {
        $data['created_by'] = Auth::id();

        $kategori = KategoriPengaduan::create($data);

        if (!$kategori) {
            Log::error('Gagal membuat kategori pengaduan', [
                'user_id' => Auth::id(),
                'data' => $data
            ]);
            throw new CustomException('Gagal membuat kategori pengaduan', 500);
        }

        return [
            'message' => 'Kategori pengaduan berhasil dibuat',
            'data' => $kategori
        ];
    }

    public function update($id, array $data)
    {
        $kategori = KategoriPengaduan::find($id);

        if (!$kategori) {
            throw new CustomException('Data kategori pengaduan tidak ditemukan', 404);
        }

        $data['updated_by'] = Auth::id();

        $kategori->update($data);

        return [
            'message' => 'Kategori pengaduan berhasil diperbarui',
            'data' => $kategori
        ];
    }

    public function delete($id)
    {
        $kategori = KategoriPengaduan::find($id);

        if (!$kategori) {
            throw new CustomException('Data kategori pengaduan tidak ditemukan', 404);
        }

        $kategori->deleted_by = Auth::id();
        $kategori->save();
        $kategori->delete();

        return [
            'message' => 'Kategori pengaduan berhasil dihapus',
        ];
    }
}

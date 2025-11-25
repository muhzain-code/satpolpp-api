<?php

namespace App\Services\Anggota;

use App\Models\Anggota\Jabatan;
use App\Exceptions\CustomException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class JabatanService
{
   public function getAll($perPage, $currentPage): array
    {
        $jabatans = Jabatan::orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $currentPage);

        if ($jabatans->isEmpty()) {
            throw new CustomException('Data jabatan tidak ditemukan', 404);
        }

       
        $jabatans->getCollection()->transform(function ($item) {
            return [
                'id' => $item->id,
                'nama' => $item->nama,
                'keterangan' => $item->keterangan,
            ];
        });

        return [
            'status' => true,
            'message' => 'Data jabatan berhasil diambil',
            'data' => [
                'current_page' => $jabatans->currentPage(),
                'per_page' => $jabatans->perPage(),
                'total' => $jabatans->total(),
                'last_page' => $jabatans->lastPage(),
                'items' => $jabatans->items()
            ]
        ];
    }

    public function getById($id)
    {
        $jabatan = Jabatan::find($id);

        if (!$jabatan) {
            throw new CustomException('Data jabatan tidak ditemukan', 404);
        }

        return [
            'status' => true,
            'message' => 'Data jabatan berhasil ditemukan',
            'data' => $jabatan
        ];
    }

    public function create(array $data)
    {
        $data['created_by'] = Auth::id();

        $jabatan = Jabatan::create($data);

        if (!$jabatan) {
            Log::error('Gagal membuat jabatan', [
                'user_id' => Auth::id(),
                'data' => $data
            ]);
            throw new CustomException('Gagal membuat jabatan', 500);
        }

        return [
            'status' => true,
            'message' => 'Jabatan berhasil dibuat',
            'data' => $jabatan
        ];
    }

    public function update($id, array $data)
    {
        $jabatan = Jabatan::find($id);

        if (!$jabatan) {
            throw new CustomException('Data jabatan tidak ditemukan', 404);
        }

        $data['updated_by'] = Auth::id();

        $jabatan->update($data);

        return [
            'status' => true,
            'message' => 'Jabatan berhasil diperbarui',
            'data' => $jabatan
        ];
    }

    public function delete($id)
    {
        $jabatan = Jabatan::find($id);

        if (!$jabatan) {
            throw new CustomException('Data jabatan tidak ditemukan', 404);
        }

        $jabatan->deleted_by = Auth::id();
        $jabatan->save();
        $jabatan->delete();

        return [
            'status' => true,
            'message' => 'Jabatan berhasil dihapus',
        ];
    }
}

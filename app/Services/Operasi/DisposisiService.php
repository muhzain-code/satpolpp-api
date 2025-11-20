<?php

namespace App\Services\Operasi;

use App\Models\Operasi\Disposisi;

class DisposisiService
{
    public function getAll($filter)
    {
        $disposisi = Disposisi::Query();

        if (isset($filter['pengaduan_id'])) {
            $disposisi->where('pengaduan_id', $filter['pengaduan_id']);
        }

        if (isset($filter['ke_anggota_id'])) {
            $disposisi->where('ke_anggota_id', $filter['ke_anggota_id']);
        }

        if (isset($filter['ke_unit_id'])) {
            $disposisi->where('ke_unit_id', $filter['ke_unit_id']);
        }

        $disposisi = $disposisi->paginate($filter['per_page'], ['*'], 'page', $filter['page']);

        $disposisi->getCollection()->transform(function ($item) {
            return [
                'id' => $item->id,
                'pengaduan_id' => $item->pengaduan_id,
                'ke_anggota_id' => $item->ke_anggota_id,
                'ke_unit_id' => $item->ke_unit_id,
                'catatan' => $item->catatan,
            ];
        });

        return [
            'message' => 'Disposisi berhasil ditampilkan',
            'data' => [
                'current_page' => $disposisi->currentPage(),
                'per_page' => $disposisi->perPage(),
                'total' => $disposisi->total(),
                'last_page' => $disposisi->lastPage(),
                'items' => $disposisi->items()
            ]
        ];
    }

    public function create($data)
    {
        $disposisi = Disposisi::create($data);

        return [
            'message' => 'Disposisi berhasil dibuat',
            'data' => $disposisi
        ];
    }

    public function getById($id)
    {
        $disposisi = Disposisi::findOrFail($id);

        return [
            'message' => 'Disposisi berhasil ditemukan',
            'data' => $disposisi
        ];
    }

    public function update($data, $id)
    {
        $disposisi = Disposisi::findOrFail($id);
        $disposisi->update($data);

        return [
            'message' => 'Disposisi berhasil diperbarui',
            'data' => $disposisi
        ];
    }

    public function delete($id)
    {
        $disposisi = Disposisi::findOrFail($id);
        $disposisi->delete();

        return [
            'message' => 'Disposisi berhasil dihapus',
            'data' => null
        ];
    }
}

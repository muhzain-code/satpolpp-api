<?php

namespace App\Services\Operasi;

use App\Exceptions\CustomException;
use Illuminate\Support\Facades\Auth;
use App\Models\Operasi\OperasiPenugasan;

class OperasiPenugasanService
{
    public function getAll($filter)
    {
        $query = OperasiPenugasan::query();

        if (!empty($filter['operasi_id'])) {
            $query->where('operasi_id', $filter['operasi_id']);
        }

        if (!empty($filter['anggota_id'])) {
            $query->where('anggota_id', $filter['anggota_id']);
        }

        if (!empty($filter['peran'])) {
            $query->where('peran', 'LIKE', '%' . $filter['peran'] . '%');
        }

        $result = $query->paginate(
            $filter['per_page'],
            ['*'],
            'page',
            $filter['page']
        );

        $result->getCollection()->transform(function ($item) {
            return [
                'id'         => $item->id,
                'operasi_id' => $item->operasi_id,
                'anggota_id' => $item->anggota_id,
                'peran'      => $item->peran,
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
            ];
        });

        return [
            'message' => 'Data penugasan operasi berhasil ditampilkan',
            'data' => [
                'current_page' => $result->currentPage(),
                'per_page'     => $result->perPage(),
                'total'        => $result->total(),
                'last_page'    => $result->lastPage(),
                'items'        => $result->items()
            ]
        ];
    }

    public function create($data)
    {
        $data['created_by'] = Auth::id();

        $penugasan = OperasiPenugasan::create($data);

        return [
            'message' => 'Penugasan operasi berhasil dibuat',
            'data' => $penugasan
        ];
    }

    public function getById($id)
    {
        $penugasan = OperasiPenugasan::find($id);

        if (!$penugasan) {
            throw new CustomException('Data penugasan tidak ditemukan', 404);
        }

        return [
            'message' => 'Penugasan operasi ditemukan',
            'data' => $penugasan
        ];
    }

    public function update($data, $id)
    {
        $penugasan = OperasiPenugasan::find($id);

        if (!$penugasan) {
            throw new CustomException('Data penugasan tidak ditemukan', 404);
        }

        $data['updated_by'] = Auth::id();

        $penugasan->update($data);

        return [
            'message' => 'Penugasan operasi berhasil diperbarui',
            'data' => $penugasan
        ];
    }

    public function delete($id)
    {
        $penugasan = OperasiPenugasan::find($id);

        if (!$penugasan) {
            throw new CustomException('Data penugasan tidak ditemukan', 404);
        }

        $penugasan->update([
            'deleted_by' => Auth::id()
        ]);

        $penugasan->delete();

        return [
            'message' => 'Penugasan operasi berhasil dihapus',
            'data' => null
        ];
    }
}

<?php

namespace App\Services\User;

use App\Models\User;

class UserService
{
    public function getAllKomandan($request, $perPage, $currentPage)
    {
        $query = User::with('anggota.jabatan:id,nama', 'anggota.unit:id,nama')
            ->role('komandan_regu')
            ->orderBy('name', 'asc');

        if ($request->has('unit_id') && !empty($request->unit_id)) {
            $query->whereHas('anggota.unit', function ($q) use ($request) {
                $q->where('id', $request->unit_id);
            });
        }

        if ($request->has('q') && !empty($request->q)) {
            $searchTerm = $request->q;
            $query->whereHas('anggota', function ($q2) use ($searchTerm) {
                $q2->where('nama_lengkap', 'like', '%' . $searchTerm . '%');
            });
        }

        $komandan = $query->paginate($perPage, ['*'], 'page', $currentPage);

        $komandan->getCollection()->transform(function ($item) {
            return [
                'id' => $item->id,
                'nama_lengkap' => $item->anggota->nama_lengkap,
                'jabatan' => $item->anggota->jabatan ? $item->anggota->jabatan->nama_jabatan : null,
                'unit' => $item->anggota->unit ? $item->anggota->unit->nama_unit : null,
            ];
        });

        return [
            'message' => 'Daftar Komandan Regu',
            'data' => $komandan,
        ];
    }
}

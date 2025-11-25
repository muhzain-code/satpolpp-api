<?php

namespace App\Services\Anggota;

use App\Models\Anggota\Unit;
use App\Exceptions\CustomException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class UnitService
{
    public function getAll($perPage, $currentPage): array
    {
        $units = Unit::orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $currentPage);

        // Opsional: Transformasi data jika ingin membatasi field yang dikirim
        
        $units->getCollection()->transform(function ($item) {
            return [
                'id' => $item->id,
                'nama' => $item->nama,
                'keterangan' => $item->keterangan,
            ];
        });

        return [
            'status' => true,
            'message' => 'Data unit berhasil diambil',
            'data' => [
                'current_page' => $units->currentPage(),
                'per_page' => $units->perPage(),
                'total' => $units->total(),
                'last_page' => $units->lastPage(),
                'items' => $units->items()
            ]
        ];
    }

    public function getById($id)
    {
        $unit = Unit::find($id);

        if (!$unit) {
            throw new CustomException('Data unit tidak ditemukan', 404);
        }

        return [
            'status' => true,
            'message' => 'Data unit berhasil ditemukan',
            'data' => $unit
        ];
    }

    public function create(array $data)
    {
        $data['created_by'] = Auth::id();

        $unit = Unit::create($data);

        if (!$unit) {
            Log::error('Gagal membuat unit', [
                'user_id' => Auth::id(),
                'data' => $data
            ]);
            throw new CustomException('Gagal membuat unit', 500);
        }

        return [
            'status' => true,
            'message' => 'Unit berhasil dibuat',
            'data' => $unit
        ];
    }

    public function update($id, array $data)
    {
        $unit = Unit::find($id);

        if (!$unit) {
            throw new CustomException('Data unit tidak ditemukan', 404);
        }

        $data['updated_by'] = Auth::id();

        $unit->update($data);

        return [
            'status' => true,
            'message' => 'Unit berhasil diperbarui',
            'data' => $unit
        ];
    }

    public function delete($id)
    {
        $unit = Unit::find($id);

        if (!$unit) {
            throw new CustomException('Data unit tidak ditemukan', 404);
        }

        $unit->deleted_by = Auth::id();
        $unit->save();
        $unit->delete();

        return [
            'status' => true,
            'message' => 'Unit berhasil dihapus',
        ];
    }
}

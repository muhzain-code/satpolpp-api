<?php

namespace App\Services\Operasi;

use Exception;
use App\Models\Operasi\Disposisi;
use Illuminate\Support\Facades\DB;
use App\Exceptions\CustomException;
use App\Models\Pengaduan\Pengaduan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class DisposisiService
{
    public function getAll($filter)
    {
        $disposisi = Disposisi::with('pengaduan', 'keAnggota', 'keUnit');

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
                'pengaduan_id' => $item->pengaduan->nomor_tiket,
                'ke_anggota_id' => $item->keAnggota->kode_anggota ?? null,
                'ke_unit_id' => $item->keUnit->nama ?? null,
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

    // public function create($data)
    // {
    //     $pengaduan = Pengaduan::find($data['pengaduan_id']);

    //     if (!$pengaduan) {
    //         throw new CustomException('Pengaduan tidak ditemukan', 404);
    //     }

    //     if ($pengaduan->status !== 'diterima') {
    //         throw new CustomException("Hanya pengaduan diterima yang bisa diproses", 422);
    //     }

    //     $pengaduan->update(['status' => 'diproses']);

    //     $data['created_by'] = Auth::id();
    //     $disposisi = Disposisi::create($data);

    //     return [
    //         'message' => 'Disposisi berhasil dibuat',
    //         'data' => $disposisi
    //     ];
    // }

    public function create($data)
    {
        try {
            return DB::transaction(function () use ($data) {
                $pengaduan = Pengaduan::find($data['pengaduan_id']);

                if (!$pengaduan) {
                    throw new CustomException('Pengaduan tidak ditemukan', 404);
                }

                if ($pengaduan->status !== 'diterima') {
                    throw new CustomException("Hanya pengaduan dengan status 'diterima' yang bisa diproses", 422);
                }

                $pengaduan->update(['status' => 'diproses', 'diproses_at' => now()]);

                $data['created_by'] = Auth::id();
                $disposisi = Disposisi::create($data);

                return [
                    'message' => 'Disposisi berhasil dibuat',
                    'data' => $disposisi
                ];
            });
        } catch (Exception $e) {
            Log::error('Gagal membuat disposisi: ' . $e->getMessage());
            throw $e;

             throw new CustomException('Gagal membuat disposisi', 422);
        }
    }

    public function getById($id)
    {
        $disposisi = Disposisi::find($id);

        if (!$disposisi) {
            throw new CustomException('Data disposisi tidak ditemukan', 404);
        }

        return [
            'message' => 'Disposisi berhasil ditemukan',
            'data' => $disposisi
        ];
    }

    public function update($data, $id)
    {
        $disposisi = Disposisi::find($id);

        if (!$disposisi) {
            throw new CustomException('Data disposisi tidak ditemukan', 404);
        }

        $data['updated_by'] = Auth::id();
        $disposisi->update($data);

        return [
            'message' => 'Disposisi berhasil diperbarui',
            'data' => $disposisi
        ];
    }

    public function delete($id)
    {
        $disposisi = Disposisi::find($id);

        if (!$disposisi) {
            throw new CustomException('Data disposisi tidak ditemukan', 404);
        }

        $disposisi->update([
            'deleted_by' => Auth::id()
        ]);
        $disposisi->delete();

        return [
            'message' => 'Disposisi berhasil dihapus',
            'data' => null
        ];
    }
}

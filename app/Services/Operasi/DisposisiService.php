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
        $disposisi = Disposisi::with('pengaduan', 'komandan')->orderBy('created_at', 'desc');

        if (isset($filter['pengaduan_id'])) {
            $disposisi->where('pengaduan_id', $filter['pengaduan_id']);
        }

        if (isset($filter['komandan_id'])) {
            $disposisi->where('komandan_id', $filter['komandan_id']);
        }

        $disposisi = $disposisi->paginate($filter['per_page'], ['*'], 'page', $filter['page']);

        $disposisi->getCollection()->transform(function ($item) {
            return [
                'id' => $item->id,
                'pengaduan_id' => $item->pengaduan->nomor_tiket,
                'komandan_id' => $item->komandan->name ?? null,
                'catatan' => $item->catatan,
                'batas_waktu' => $item->batas_waktu,
                'status' => $item->status,
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

        $disposisi->getCollection()->transform(function ($item) {
            $pengaduan = $item->pengaduan;
            return [
                'id' => $item->id,
                'pengaduan' => $pengaduan ? [
                    'id' => $pengaduan->id,
                    'nomor_tiket' => $pengaduan->nomor_tiket,
                    'kategori' => $pengaduan->kategoriPengaduan->nama ?? null,
                    'deskripsi' => $pengaduan->deskripsi,
                    'lat' => $pengaduan->lat,
                    'lng' => $pengaduan->lng,
                    'kecamatan' => $pengaduan->kecamatan ? $pengaduan->kecamatan->nama : null,
                    'desa' => $pengaduan->desa ? $pengaduan->desa->nama : null,
                ] : null,
                'komandan' => $item->komandan->name ?? null,
                'catatan' => $item->catatan,
                'batas_waktu' => $item->batas_waktu,
                'status' => $item->status,
            ];
        });

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

    public function getDisposisiKomandan($request)
    {
        $user = Auth::user();

        if (!$user->anggota || !$user->anggota->id || !$user->anggota->unit_id) {
            throw new CustomException('User tidak terkait dengan anggota', 403);
        }

        $anggota = $user->anggota;

        $disposisiQuery = Disposisi::with(['pengaduan.kategoriPengaduan', 'komandan'])
            ->whereHas('pengaduan', fn($q) => $q->where('status', 'diproses'))
            ->where('komandan_id', $anggota->unit_id)
            ->orderBy('created_at', 'desc');

        $disposisi = $disposisiQuery->paginate($request->per_page, ['*'], 'page', $request->page);

        $disposisi->getCollection()->transform(function ($item) {
            $pengaduan = $item->pengaduan;
            return [
                'id' => $item->id,
                'pengaduan' => $pengaduan ? [
                    'id' => $pengaduan->id,
                    'nomor_tiket' => $pengaduan->nomor_tiket,
                    'kategori' => $pengaduan->kategoriPengaduan->nama ?? null,
                    'deskripsi' => $pengaduan->deskripsi,
                    'lat' => $pengaduan->lat,
                    'lng' => $pengaduan->lng,
                    'kecamatan' => $pengaduan->kecamatan ? $pengaduan->kecamatan->nama : null,
                    'desa' => $pengaduan->desa ? $pengaduan->desa->nama : null,
                ] : null,
                'komandan' => $item->komandan->name ?? null,
                'catatan' => $item->catatan,
                'batas_waktu' => $item->batas_waktu,
                'status' => $item->status,
            ];
        });

        return [
            'message' => $disposisi->isEmpty() ? 'Tidak ada disposisi untuk anggota ini' : 'Disposisi berhasil ditampilkan',
            'data' => [
                'current_page' => $disposisi->currentPage(),
                'per_page' => $disposisi->perPage(),
                'total' => $disposisi->total(),
                'last_page' => $disposisi->lastPage(),
                'items' => $disposisi->getCollection()->toArray()
            ]
        ];
    }
}

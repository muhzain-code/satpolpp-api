<?php

namespace App\Services\Humas;

use App\Exceptions\CustomException;
use App\Models\Humas\Konten;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class KontenService
{
    public function index($currentPage, $perPage): array
    {
        $Konten = Konten::paginate($perPage, ['*'], 'page', $currentPage);

        if (!$Konten) {
            throw new CustomException('Data tidak ditemukan');
        }

        $Konten->getCollection()->transform(function ($item) {
            return [
                'id'                => $item->id,
                'tipe'              => $item->tipe,
                'judul'             => $item->judul,
                'tampilkan_publik'  => $item->tampilkan_publik,
            ];
        });

        return [
            'message' => 'data berhasil ditampilkan',
            'data'    => $Konten
        ];
    }

    public function store(array $data): array
    {
        DB::beginTransaction();
        try {
            $UserId = Auth::id();

            if (isset($data['path_gambar']) && $data['path_gambar']->isValid()) {
                $path = $data['path_gambar']->store('Konten', 'public');
                $data['path_gambar'] = $path;
            }

            $Konten = Konten::create([
                'tipe'              => $data['tipe'],
                'judul'             => $data['judul'],
                'isi'               => $data['isi'],
                'path_gambar'       => $path,
                'tampilkan_publik'  => $data['tampilkan_publik'],
                'created_by'        => $UserId
            ]);

            DB::commit();

            return [
                'message' => 'berhasil menambahkan data Konten',
                'data' => $Konten
            ];
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Gagal menambahkan data Konten', [
                'error' => $e->getMessage()
            ]);

            if ($e instanceof CustomException) {
                throw $e;
            }

            throw new CustomException('Gagal menambahkan data Konten');
        }
    }

    public function show($Id): array
    {
        $Konten = Konten::where($Id)->first();

        if (!$Konten) {
            throw new CustomException('Data tidak ditemukan');
        }

        $Konten->getCollection()->transform(function ($item) {
            return [
                'id'                => $item->id,
                'tipe'              => $item->tipe,
                'judul'             => $item->judul,
                'isi'               => $item->isi,
                'path_gambar'       => $item->path_gambar,
                'tampilkan_publik'  => $item->tampilkan_publik,
            ];
        });

        return [
            'message' => 'data berhasil ditampilkan',
            'data'    => $Konten
        ];
    }

    public function update(array $data, $Id): array
    {
        DB::beginTransaction();
        try {
            $UserId = Auth::id();
            $Konten = Konten::where($Id)->first();
            if (!$Konten) {
                throw new CustomException('Data tidak ditemukan');
            }
            if (isset($data['path_gambar']) && $data['path_gambar'] instanceof \Illuminate\Http\UploadedFile) {
                if ($Konten->path_gambar && Storage::disk('public')->exists($Konten->path_gambar)) {
                    Storage::disk('public')->delete($Konten->path_gambar);
                }
                $data['path_gambar'] = $data['path_gambar']->store('Konten', 'public');
            }

            $Konten->update([
                'tipe'              => $data['tipe'],
                'judul'             => $data['judul'],
                'isi'               => $data['isi'],
                'path_gambar'       => $data['path_gambar'] ?? $Konten->path_gambar,
                'tampilkan_publik'  => $data['tampilkan_publik'],
                'updated_by'        => $UserId,
            ]);
            DB::commit();

            return [
                'message' => 'Data berhasil diperbarui',
                'data' => $Konten
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Gagal menambahkan data Konten', [
                'error' => $e->getMessage()
            ]);

            if ($e instanceof CustomException) {
                throw $e;
            }

            throw new CustomException('Gagal memperbarui data Konten');
        }
    }

    public function destroy($Id): array
    {
        $Konten = Konten::where($Id)->first();
        if (!$Konten) {
            throw new CustomException('Data tidak ditemukan');
        }
        $Konten->delete();

        return [
            'message' =>  'data berhasil di hapus'
        ];
    }
}

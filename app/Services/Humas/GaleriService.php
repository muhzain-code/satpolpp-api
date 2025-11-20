<?php

namespace App\Services\Humas;

use App\Exceptions\CustomException;
use App\Models\Humas\Galeri;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GaleriService
{
    public function index($currentPage, $perPage): array
    {
        $Galery = Galeri::paginate($perPage, ['*'], 'page', $currentPage);

        if (!$Galery) {
            throw new CustomException('Data tidak ditemukan');
        }

        $Galery->getCollection()->transform(function ($item) {
            return [
                'id'                => $item->id,
                'tipe'              => $item->tipe,
                'judul'             => $item->judul,
            ];
        });

        return [
            'message' => 'data berhasil ditampilkan',
            'data'    => $Galery
        ];
    }

    public function store(array $data): array
    {
        DB::beginTransaction();
        try {
            $UserId = Auth::id();

            if (isset($data['path_file']) && $data['path_file']->isValid()) {
                $path = $data['path_file']->store('Galeri', 'public');
                $data['path_file'] = $path;
            }

            $Galery = Galeri::create([
                'tipe'              => $data['tipe'],
                'judul'             => $data['judul'],
                'path_file'       => $path,
                'created_by'        => $UserId
            ]);

            DB::commit();

            return [
                'message' => 'berhasil menambahkan data galeri',
                'data' => $Galery
            ];
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Gagal menambahkan data galeri', [
                'error' => $e->getMessage()
            ]);

            if ($e instanceof CustomException) {
                throw $e;
            }

            throw new CustomException('Gagal menambahkan data galeri');
        }
    }

    public function show($Id): array
    {
        $Galery = Galeri::where($Id)->first();

        if (!$Galery) {
            throw new CustomException('Data tidak ditemukan');
        }

        $Galery->getCollection()->transform(function ($item) {
            return [
                'id'                => $item->id,
                'tipe'              => $item->tipe,
                'judul'             => $item->judul,
                'path_file'       => $item->path_file = url(Storage::url($item->path_file)),
            ];
        });

        return [
            'message' => 'data berhasil ditampilkan',
            'data'    => $Galery
        ];
    }

    public function update(array $data, $Id): array
    {
        DB::beginTransaction();
        try {
            $UserId = Auth::id();
            $Galery = Galeri::where($Id)->first();
            if (!$Galery) {
                throw new CustomException('Data tidak ditemukan');
            }
            if (isset($data['path_file']) && $data['path_file'] instanceof \Illuminate\Http\UploadedFile) {
                if ($Galery->path_file && Storage::disk('public')->exists($Galery->path_file)) {
                    Storage::disk('public')->delete($Galery->path_file);
                }
                $data['path_file'] = $data['path_file']->store('Galeri', 'public');
            }

            $Galery->update([
                'tipe'              => $data['tipe'],
                'judul'             => $data['judul'],
                'path_file'       => $data['path_file'] ?? $Galery->path_file,
                'updated_by'        => $UserId,
            ]);
            DB::commit();

            return [
                'message' => 'Data berhasil diperbarui',
                'data' => $Galery
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Gagal menambahkan data galeri', [
                'error' => $e->getMessage()
            ]);

            if ($e instanceof CustomException) {
                throw $e;
            }

            throw new CustomException('Gagal memperbarui data galeri');
        }
    }

    public function destroy($Id): array
    {
        $Galery = Galeri::where($Id)->first();
        if (!$Galery) {
            throw new CustomException('Data tidak ditemukan');
        }

        if (Storage::disk('public')->exists($Galery->path_file)) {
            Storage::disk('public')->delete($Galery->path_file);
        }
        $Galery->delete();

        return [
            'message' =>  'data berhasil di hapus'
        ];
    }
}

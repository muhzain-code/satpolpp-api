<?php

namespace App\Services\Humas;

use App\Exceptions\CustomException;
use App\Models\Humas\Galeri;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;


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
            'data'    => [
                'current_page'  => $Galery->currentPage(),
                'per_page'      => $Galery->perPage(),
                'total'         => $Galery->total(),
                'last_page'     => $Galery->lastPage(),
                'items'         => $Galery->items(),
            ]
        ];
    }

    public function store(array $data): array
    {
        DB::beginTransaction();
        try {
            $UserId = Auth::id();
            $path = null;
            $tipe = null;

            if (isset($data['path_file']) && $data['path_file']->isValid()) {

                $extension = strtolower($data['path_file']->getClientOriginalExtension());

                $fotoExt  = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                $videoExt = ['mp4', 'mov', 'avi', 'mkv'];

                if (in_array($extension, $fotoExt)) {
                    $tipe = 'foto';
                } elseif (in_array($extension, $videoExt)) {
                    $tipe = 'video';
                } else {
                    throw new CustomException('Format file tidak didukung');
                }

                $path = $data['path_file']->store('Galeri', 'public');
            }

            $Galery = Galeri::create([
                'tipe'       => $tipe,
                'judul'      => $data['judul'],
                'path_file'  => $path,
                'created_by' => $UserId
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
        $Galery = Galeri::find($Id);

        if (!$Galery) {
            throw new CustomException('Data tidak ditemukan');
        }

        $Galery->path_file = url(Storage::url($Galery->path_file));

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
            $Galery = Galeri::find($Id);

            if (!$Galery) {
                throw new CustomException('Data tidak ditemukan');
            }

            $newFilePath = $Galery->path_file;
            $newTipe = $Galery->tipe;

            if (isset($data['path_file']) && $data['path_file'] instanceof UploadedFile) {

                $extension = strtolower($data['path_file']->getClientOriginalExtension());
                $fotoExt  = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                $videoExt = ['mp4', 'mov', 'avi', 'mkv'];

                if (in_array($extension, $fotoExt)) {
                    $newTipe = 'foto';
                } elseif (in_array($extension, $videoExt)) {
                    $newTipe = 'video';
                } else {
                    throw new CustomException('Format file tidak didukung');
                }

                if ($Galery->path_file && Storage::disk('public')->exists($Galery->path_file)) {
                    Storage::disk('public')->delete($Galery->path_file);
                }

                $newFilePath = $data['path_file']->store('Galeri', 'public');
            }

            $Galery->update([
                'tipe'       => $newTipe,
                'judul'      => $data['judul'],
                'path_file'  => $newFilePath,
                'updated_by' => $UserId,
            ]);

            DB::commit();

            return [
                'message' => 'Data berhasil diperbarui',
                'data' => $Galery
            ];
        } catch (\Throwable $e) {

            DB::rollBack();

            Log::error('Gagal memperbarui data galeri', [
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
        $Galery = Galeri::find($Id);
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

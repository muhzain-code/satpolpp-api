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
    // role superadmin / humas
    public function ambildaftargaleri($currentPage, $perPage): array
    {
        $Galery = Galeri::latest()->paginate($perPage, ['*'], 'page', $currentPage);

        $Galery->getCollection()->transform(function ($item) {
            return [
                'id'        => $item->id,
                'judul'     => $item->judul,
                'tipe'      => $item->tipe,
                'status'    => (bool) $item->status,
                'path_file' => $item->path_file ? url(Storage::url($item->path_file)) : null,
                'created_at' => $item->created_at,
            ];
        });

        return [
            'message' => 'Data berhasil ditampilkan',
            'data'    => [
                'current_page' => $Galery->currentPage(),
                'per_page'     => $Galery->perPage(),
                'total'        => $Galery->total(),
                'last_page'    => $Galery->lastPage(),
                'items'        => $Galery->items(),
            ]
        ];
    }

    public function simpangaleribaru(array $data): array
    {
        DB::beginTransaction();
        try {
            $UserId = Auth::id();
            $path = null;
            $tipe = 'foto';

            // Handle Upload File
            if (isset($data['path_file']) && $data['path_file'] instanceof \Illuminate\Http\UploadedFile && $data['path_file']->isValid()) {

                $extension = strtolower($data['path_file']->getClientOriginalExtension());
                $fotoExt   = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                $videoExt  = ['mp4', 'mov', 'avi', 'mkv'];

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
                'judul'      => $data['judul'] ?? null,
                'path_file'  => $path,
                'tipe'       => $tipe,
                'status'     => $data['status'] ?? true, // Default true jika tidak dikirim
                'created_by' => $UserId,
            ]);

            DB::commit();

            return [
                'message' => 'Berhasil menambahkan data galeri',
                'data'    => $Galery
            ];
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Gagal menambahkan data galeri', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            if ($e instanceof CustomException) {
                throw $e;
            }
            throw new CustomException('Gagal menambahkan data galeri');
        }
    }

    public function ambildetailgaleri($Id): array
    {
        $Galery = Galeri::find($Id);

        if (!$Galery) {
            throw new CustomException('Data tidak ditemukan');
        }

        $Galery->file_url = $Galery->path_file ? url(Storage::url($Galery->path_file)) : null;

        return [
            'message' => 'Data berhasil ditampilkan',
            'data'    => $Galery
        ];
    }

    public function perbaruidatagaleri(array $data, $Id): array
    {
        DB::beginTransaction();
        try {
            $UserId = Auth::id();
            $Galery = Galeri::find($Id);

            if (!$Galery) {
                throw new CustomException('Data tidak ditemukan');
            }

            $newFilePath = $Galery->path_file;
            $newTipe     = $Galery->tipe;

            if (isset($data['path_file']) && $data['path_file'] instanceof \Illuminate\Http\UploadedFile && $data['path_file']->isValid()) {

                $extension = strtolower($data['path_file']->getClientOriginalExtension());
                $fotoExt   = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                $videoExt  = ['mp4', 'mov', 'avi', 'mkv'];

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
                'judul'      => $data['judul'] ?? $Galery->judul,
                'tipe'       => $newTipe,
                'path_file'  => $newFilePath,
                'status'     => isset($data['status']) ? $data['status'] : $Galery->status,
                'updated_by' => $UserId,
            ]);

            DB::commit();

            return [
                'message' => 'Data berhasil diperbarui',
                'data'    => $Galery
            ];
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Gagal memperbarui data galeri', [
                'error' => $e->getMessage(),
                'id' => $Id
            ]);

            if ($e instanceof CustomException) {
                throw $e;
            }
            throw new CustomException('Gagal memperbarui data galeri');
        }
    }

    public function hapusgaleri($Id): array
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

    public function ambilGaleriPublik($currentPage, $perPage): array
    {
        $galeri = Galeri::where('status', true)
            ->latest()
            ->paginate($perPage, ['*'], 'page', $currentPage);

        if ($galeri->isEmpty()) {
            throw new CustomException('Belum ada galeri yang dipublikasikan');
        }

        $galeri->getCollection()->transform(function ($item) {
            return [
                'id'         => $item->id,
                'judul'      => $item->judul,
                'tipe'       => $item->tipe,
                'file_url'   => $item->path_file ? url(Storage::url($item->path_file)) : null,
                'tanggal'    => $item->created_at->format('d F Y'),
            ];
        });

        return [
            'message' => 'Daftar galeri publik berhasil diambil',
            'data'    => [
                'current_page' => $galeri->currentPage(),
                'per_page'     => $galeri->perPage(),
                'total'        => $galeri->total(),
                'last_page'    => $galeri->lastPage(),
                'items'        => $galeri->items(),
            ]
        ];
    }
}

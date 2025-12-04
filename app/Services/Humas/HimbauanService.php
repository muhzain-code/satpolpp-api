<?php

namespace App\Services\Humas;

use App\Exceptions\CustomException;
use App\Models\Humas\Himbauan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class HimbauanService
{
    private function generateUniqueSlug($baseSlug, $ignoreId = null)
    {
        $slug = $baseSlug;
        $counter = 1;

        while (Himbauan::where('slug', $slug)
            ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
            ->exists()
        ) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    public function listHimbauan($currentPage, $perPage, $search = null): array
    {
        $query = Himbauan::latest();

        if ($search) {
            $query->where('judul', 'like', '%' . $search . '%');
        }

        $himbauan = $query->paginate($perPage, ['*'], 'page', $currentPage)
            ->through(function ($item) {
                return [
                    'id'               => $item->id,
                    'judul'            => $item->judul,
                    'slug'             => $item->slug,
                    'isi'              => Str::limit(strip_tags($item->isi), 100),
                    'tampilkan_publik' => (bool) $item->tampilkan_publik,
                    'path_gambar'      => $item->path_gambar ? url(Storage::url($item->path_gambar)) : null,
                    'published_at'     => $item->published_at,
                ];
            });

        return [
            'message' => 'Data himbauan berhasil ditampilkan',
            'data'    => [
                'current_page' => $himbauan->currentPage(),
                'per_page'     => $himbauan->perPage(),
                'total'        => $himbauan->total(),
                'last_page'    => $himbauan->lastPage(),
                'items'        => $himbauan->items(),
            ]
        ];
    }

    public function createHimbauan(array $data): array
    {
        DB::beginTransaction();

        try {
            $userId = Auth::id();
            $path = null;

            if (isset($data['path_gambar']) && $data['path_gambar'] instanceof \Illuminate\Http\UploadedFile) {
                if ($data['path_gambar']->isValid()) {
                    $path = $data['path_gambar']->store('himbauan', 'public');
                }
            }

            $slug = Str::slug($data['judul']);
            $slug = $this->generateUniqueSlug($slug);

            $publishedAt = ($data['tampilkan_publik'] ?? false) ? now() : null;

            $himbauan = Himbauan::create([
                'judul'            => $data['judul'],
                'slug'             => $slug,
                'isi'              => $data['isi'] ?? null,
                'path_gambar'      => $path,
                'tampilkan_publik' => $data['tampilkan_publik'] ?? true,
                'published_at'     => $publishedAt,
                'created_by'       => $userId,
            ]);

            DB::commit();

            return [
                'message' => 'Berhasil menambahkan Himbauan',
                'data'    => $himbauan
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Gagal tambah himbauan', ['error' => $e->getMessage()]);
            throw new CustomException('Gagal menambahkan data Himbauan');
        }
    }

    public function showHimbauanById($id): array
    {
        $himbauan = Himbauan::find($id);

        if (!$himbauan) {
            throw new CustomException('Data himbauan tidak ditemukan');
        }

        $himbauan->path_gambar = $himbauan->path_gambar
            ? url(Storage::url($himbauan->path_gambar))
            : null;

        return [
            'message' => 'Detail himbauan berhasil ditampilkan',
            'data'    => $himbauan
        ];
    }

    public function updateHimbauanById(array $data, $id): array
    {
        DB::beginTransaction();

        try {
            $userId = Auth::id();
            $himbauan = Himbauan::find($id);

            if (!$himbauan) {
                throw new CustomException('Data himbauan tidak ditemukan');
            }

            if (isset($data['path_gambar']) && $data['path_gambar'] instanceof \Illuminate\Http\UploadedFile) {
                if ($himbauan->path_gambar && Storage::disk('public')->exists($himbauan->path_gambar)) {
                    Storage::disk('public')->delete($himbauan->path_gambar);
                }
                $data['path_gambar'] = $data['path_gambar']->store('himbauan', 'public');
            } else {
                $data['path_gambar'] = $himbauan->path_gambar;
            }

            $publishedAt = $himbauan->published_at;
            if (!$himbauan->tampilkan_publik && ($data['tampilkan_publik'] ?? false)) {
                $publishedAt = now();
            }

            $newSlug = $himbauan->slug;
            if ($himbauan->judul !== $data['judul']) {
                $newSlug = Str::slug($data['judul']);
                $newSlug = $this->generateUniqueSlug($newSlug, $himbauan->id);
            }

            $himbauan->update([
                'judul'            => $data['judul'],
                'slug'             => $newSlug,
                'isi'              => $data['isi'],
                'path_gambar'      => $data['path_gambar'],
                'tampilkan_publik' => $data['tampilkan_publik'],
                'published_at'     => $publishedAt,
                'updated_by'       => $userId,
            ]);

            DB::commit();

            return [
                'message' => 'Data himbauan berhasil diperbarui',
                'data'    => $himbauan
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Gagal update himbauan', ['error' => $e->getMessage()]);
            throw new CustomException('Gagal memperbarui data Himbauan');
        }
    }

    public function deleteHimbauanById($id): array
    {
        $himbauan = Himbauan::find($id);

        if (!$himbauan) {
            throw new CustomException('Data himbauan tidak ditemukan');
        }

        if ($himbauan->path_gambar && Storage::disk('public')->exists($himbauan->path_gambar)) {
            Storage::disk('public')->delete($himbauan->path_gambar);
        }
        $himbauan->delete();

        return [
            'message' => 'Data himbauan berhasil dihapus'
        ];
    }

    public function himbauanPublik($request): array
    {
        $limit = $request->input('limit', 10);

        $himbauan = Himbauan::select([
            'judul',
            'slug',
            'path_gambar',
            'published_at',
            'isi',
            'tampilkan_publik'
        ])
            ->where('tampilkan_publik', true)
            ->orderBy('published_at', 'desc')
            ->limit($limit)
            ->get()
            ->transform(function ($item) {
                return [
                    'tipe'              => 'himbauan',
                    'judul'             => $item->judul,
                    'slug'              => $item->slug,
                    'gambar'            => $item->path_gambar ? url(Storage::url($item->path_gambar)) : null,
                    'tanggal'           => $item->published_at,
                    'deskripsi_singkat' => Str::limit(strip_tags($item->isi), 120, '...')
                ];
            });

        return [
            'message' => 'Data himbauan berhasil ditampilkan',
            'data'    => $himbauan
        ];
    }

    public function detailHimbauan($slug): array
    {
        $himbauan = Himbauan::where('slug', $slug)
            ->where('tampilkan_publik', true)
            ->select([
                'id',
                'judul',
                'slug',
                'isi',
                'path_gambar',
                'published_at',
            ])
            ->first();

        if (!$himbauan) {
            throw new CustomException('Data himbauan tidak ditemukan');
        }

        // Format URL gambar
        $urlGambar = $himbauan->path_gambar ? url(Storage::url($himbauan->path_gambar)) : null;

        return [
            'message' => 'Detail himbauan berhasil ditampilkan',
            'data'    => [
                'tipe'         => 'himbauan',
                'judul'        => $himbauan->judul,
                'slug'         => $himbauan->slug,
                'isi'          => $himbauan->isi,
                'gambar'       => $urlGambar,
                'tanggal'      => $himbauan->published_at,
            ]
        ];
    }
}

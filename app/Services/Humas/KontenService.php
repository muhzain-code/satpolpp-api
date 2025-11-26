<?php

namespace App\Services\Humas;

use App\Exceptions\CustomException;
use App\Models\Humas\Konten;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class KontenService
{
    // Role SuperAdmin
    public function listKonten($currentPage, $perPage): array
    {
        $konten = Konten::paginate($perPage, ['*'], 'page', $currentPage);

        return [
            'message' => 'data berhasil ditampilkan',
            'data'    => [
                'current_page'  => $konten->currentPage(),
                'per_page'      => $konten->perPage(),
                'total'         => $konten->total(),
                'last_page'     => $konten->lastPage(),
                'items'         => $konten->items(),
            ]
        ];
    }

    public function createKonten(array $data): array
    {
        DB::beginTransaction();

        try {
            $userId = Auth::id();
            $path = null;

            if (isset($data['path_gambar']) && $data['path_gambar']->isValid()) {
                $path = $data['path_gambar']->store('konten', 'public');
            }

            $slug = Str::slug($data['judul']);
            $slug = $this->generateUniqueSlug($slug);

            $publishedAt = ($data['tampilkan_publik'] ?? false) ? now() : null;

            $konten = Konten::create([
                'tipe'              => $data['tipe'],
                'judul'             => $data['judul'],
                'slug'              => $slug,
                'isi'               => $data['isi'] ?? null,
                'path_gambar'       => $path,
                'tampilkan_publik'  => $data['tampilkan_publik'],
                'published_at'      => $publishedAt,
                'created_by'        => $userId,
            ]);
            DB::commit();

            return [
                'message' => 'berhasil menambahkan data Konten',
                'data'    => $konten
            ];
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Gagal menambahkan data Konten', [
                'error' => $e->getMessage()
            ]);

            throw new CustomException('Gagal menambahkan data Konten');
        }
    }


    public function showKontenById($id): array
    {
        $konten = Konten::find($id);

        if (!$konten) {
            throw new CustomException('Data tidak ditemukan');
        }

        return [
            'message' => 'data berhasil ditampilkan',
            'data'    => $konten
        ];
    }

    public function updateKontenById(array $data, $id): array
    {
        DB::beginTransaction();

        try {
            $userId = Auth::id();
            $konten = Konten::find($id);

            if (!$konten) {
                throw new CustomException('Data tidak ditemukan');
            }

            if (isset($data['path_gambar']) && $data['path_gambar'] instanceof \Illuminate\Http\UploadedFile) {

                if ($konten->path_gambar && Storage::disk('public')->exists($konten->path_gambar)) {
                    Storage::disk('public')->delete($konten->path_gambar);
                }

                $data['path_gambar'] = $data['path_gambar']->store('konten', 'public');
            } else {
                $data['path_gambar'] = $konten->path_gambar;
            }

            $publishedAt = $konten->published_at;

            if ($konten->tampilkan_publik == false && $data['tampilkan_publik'] == true) {
                $publishedAt = now();
            }

            if (!empty($data['published_at'])) {
                $publishedAt = $data['published_at'];
            }

            $newSlug = $konten->slug;
            if ($konten->judul !== $data['judul']) {
                $newSlug = Str::slug($data['judul']);
                $newSlug = $this->generateUniqueSlug($newSlug, $konten->id);
            }

            $konten->update([
                'tipe'              => $data['tipe'],
                'judul'             => $data['judul'],
                'slug'              => $newSlug,
                'isi'               => $data['isi'],
                'path_gambar'       => $data['path_gambar'],
                'tampilkan_publik'  => $data['tampilkan_publik'],
                'published_at'      => $publishedAt,
                'updated_by'        => $userId,
            ]);

            DB::commit();

            return [
                'message' => 'Data berhasil diperbarui',
                'data'    => $konten
            ];
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Gagal memperbarui data Konten (SuperAdmin)', [
                'error' => $e->getMessage()
            ]);

            throw new CustomException('Gagal memperbarui data Konten');
        }
    }

    public function deleteKontenById($id): array
    {
        $konten = Konten::find($id);

        if (!$konten) {
            throw new CustomException('Data tidak ditemukan');
        }

        if ($konten->path_gambar && Storage::disk('public')->exists($konten->path_gambar)) {
            Storage::disk('public')->delete($konten->path_gambar);
        }

        $konten->delete();

        return [
            'message' => 'data berhasil dihapus'
        ];
    }

    private function generateUniqueSlug($baseSlug, $ignoreId = null)
    {
        $slug = $baseSlug;
        $counter = 1;

        while (Konten::where('slug', $slug)
            ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
            ->exists()
        ) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    // Role Masyarakat
    public function KontenPublik($request): array
    {
        $limit = $request->input('limit', 25);

        $konten = Konten::select([
            'tipe',
            'judul',
            'slug',
            'path_gambar',
            'published_at',
        ])
            ->where('tampilkan_publik', true)
            ->orderBy('published_at', 'desc')
            ->limit($limit)
            ->get()
            ->transform(function ($item) {
                $item->path_gambar = $item->path_gambar
                    ? Storage::url($item->path_gambar)
                    : null;

                return $item;
            });

        return [
            'message' => 'data berhasil ditampilkan',
            'data'  => $konten
        ];
    }

    public function detailKonten($slug): array
    {
        $konten = Konten::where('slug', $slug)
            ->where('tampilkan_publik', true)
            ->select([
                'tipe',
                'judul',
                'slug',
                'isi',
                'path_gambar',
                'published_at'
            ])
            ->first();

        if (!$konten) {
            throw new CustomException('Data tidak ditemukan');
        }

        if ($konten->path_gambar) {
            $konten->path_gambar = Storage::url($konten->path_gambar);
        }

        return [
            'message' => 'data berhasil ditampilkan',
            'data' => $konten
        ];
    }
}

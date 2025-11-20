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
    public function index($currentPage, $perPage): array
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

public function store(array $data): array
{
    DB::beginTransaction();

    try {
        $userId = Auth::id();
        $path = null;

        // upload gambar
        if (isset($data['path_gambar']) && $data['path_gambar']->isValid()) {
            $path = $data['path_gambar']->store('konten', 'public');
        }

        // generate slug unik
        $slug = Str::slug($data['judul']);
        $slug = $this->generateUniqueSlug($slug);

        // otomatis set published_at
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


    public function show($slug): array
    {
        $konten = Konten::where('slug', $slug)->first();

        if (!$konten) {
            throw new CustomException('Data tidak ditemukan');
        }

        return [
            'message' => 'data berhasil ditampilkan',
            'data'    => $konten
        ];
    }

    public function update(array $data, $slug): array
    {
        DB::beginTransaction();

        try {
            $userId = Auth::id();
            $konten = Konten::where('slug', $slug)->first();

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
                if (!$publishedAt) {
                    $publishedAt = now();
                }
            }

            if ($data['tampilkan_publik'] == true && !empty($data['published_at'])) {
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

            Log::error('Gagal memperbarui data Konten', [
                'error' => $e->getMessage()
            ]);

            throw new CustomException('Gagal memperbarui data Konten');
        }
    }

    public function destroy($slug): array
    {
        $konten = Konten::where('slug', $slug)->first();

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
}

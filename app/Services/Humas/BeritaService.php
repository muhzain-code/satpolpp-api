<?php

namespace App\Services\Humas;

use App\Exceptions\CustomException;
use App\Models\Humas\Berita;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BeritaService
{
    private function generateUniqueSlug($baseSlug, $ignoreId = null)
    {
        $slug = $baseSlug;
        $counter = 1;

        // Cek ke tabel 'berita' bukan 'konten' lagi
        while (Berita::where('slug', $slug)
            ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
            ->exists()
        ) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    public function listBerita($currentPage, $perPage): array
    {
        // Tidak perlu where('tipe', 'berita') karena tabel sudah khusus berita
        $berita = Berita::latest()
            ->paginate($perPage, ['*'], 'page', $currentPage);

        $berita->getCollection()->transform(function ($item) {
            return [
                'id'               => $item->id,
                'judul'            => $item->judul,
                'slug'             => $item->slug,
                'kategori'         => $item->Kategori, // Sesuai nama kolom di DB (Case Sensitive)
                'isi'              => $item->isi,
                'path_gambar'      => $item->path_gambar ? url(Storage::url($item->path_gambar)) : null,
                'tampilkan_publik' => (bool) $item->tampilkan_publik,
                'published_at'     => $item->published_at,
                'created_by_name'  => $item->createdBy->name ?? null, // Opsional: jika ada relasi
            ];
        });

        return [
            'message' => 'Data berita berhasil ditampilkan',
            'data'    => [
                'current_page' => $berita->currentPage(),
                'per_page'     => $berita->perPage(),
                'total'        => $berita->total(),
                'last_page'    => $berita->lastPage(),
                'items'        => $berita->items(),
            ]
        ];
    }

    public function createBerita(array $data): array
    {
        DB::beginTransaction();

        try {
            $userId = Auth::id();
            $path = null;

            if (isset($data['path_gambar']) && $data['path_gambar']->isValid()) {
                $path = $data['path_gambar']->store('berita', 'public'); // Folder penyimpanan disesuaikan
            }

            $slug = Str::slug($data['judul']);
            $slug = $this->generateUniqueSlug($slug);

            $publishedAt = ($data['tampilkan_publik'] ?? false) ? now() : null;

            $berita = Berita::create([
                'judul'            => $data['judul'],
                'slug'             => $slug,
                'Kategori'         => $data['kategori'], // Pastikan input form menggunakan key 'kategori'
                'isi'              => $data['isi'] ?? null,
                'path_gambar'      => $path,
                'tampilkan_publik' => $data['tampilkan_publik'] ?? true,
                'published_at'     => $publishedAt,
                'created_by'       => $userId,
            ]);

            DB::commit();

            return [
                'message' => 'Berhasil menambahkan data Berita',
                'data'    => $berita
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Gagal tambah berita', ['error' => $e->getMessage()]);
            throw new CustomException('Gagal menambahkan data Berita');
        }
    }

    public function showBeritaById($id): array
    {
        $berita = Berita::find($id);

        if (!$berita) {
            throw new CustomException('Data berita tidak ditemukan');
        }

        $berita->path_gambar_url = $berita->path_gambar ? url(Storage::url($berita->path_gambar)) : null;

        return [
            'message' => 'Data berita berhasil ditampilkan',
            'data'    => $berita
        ];
    }

    public function updateBeritaById(array $data, $id): array
    {
        DB::beginTransaction();

        try {
            $userId = Auth::id();
            $berita = Berita::find($id);

            if (!$berita) {
                throw new CustomException('Data berita tidak ditemukan');
            }

            // Handle Upload Gambar
            if (isset($data['path_gambar']) && $data['path_gambar'] instanceof \Illuminate\Http\UploadedFile) {
                // Hapus gambar lama jika ada
                if ($berita->path_gambar && Storage::disk('public')->exists($berita->path_gambar)) {
                    Storage::disk('public')->delete($berita->path_gambar);
                }
                $data['path_gambar'] = $data['path_gambar']->store('berita', 'public');
            } else {
                $data['path_gambar'] = $berita->path_gambar;
            }

            // Handle Published At
            $publishedAt = $berita->published_at;
            // Jika status berubah dari private ke public, set waktu sekarang
            if (!$berita->tampilkan_publik && ($data['tampilkan_publik'] ?? false)) {
                $publishedAt = now();
            }
            // Jika user mengirim manual tanggal publish
            if (!empty($data['published_at'])) {
                $publishedAt = $data['published_at'];
            }

            // Handle Slug
            $newSlug = $berita->slug;
            if ($berita->judul !== $data['judul']) {
                $newSlug = Str::slug($data['judul']);
                $newSlug = $this->generateUniqueSlug($newSlug, $berita->id);
            }

            $berita->update([
                'judul'            => $data['judul'],
                'slug'             => $newSlug,
                'Kategori'         => $data['kategori'],
                'isi'              => $data['isi'],
                'path_gambar'      => $data['path_gambar'],
                'tampilkan_publik' => $data['tampilkan_publik'],
                'published_at'     => $publishedAt,
                'updated_by'       => $userId,
            ]);

            DB::commit();

            return [
                'message' => 'Data berita berhasil diperbarui',
                'data'    => $berita
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Gagal update berita', ['error' => $e->getMessage()]);
            throw new CustomException('Gagal memperbarui data Berita');
        }
    }
    public function deleteBeritaById($id): array
    {
        DB::beginTransaction();

        try {
            $berita = Berita::find($id);

            if (!$berita) {
                throw new CustomException('Data berita tidak ditemukan');
            }

            $userId = Auth::id();

            if ($berita->path_gambar && Storage::disk('public')->exists($berita->path_gambar)) {
                Storage::disk('public')->delete($berita->path_gambar);
            }
            $berita->delete();

            DB::commit();

            return [
                'message' => 'Data berita dan file gambar berhasil dihapus',
                'data'    => null
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Gagal hapus berita', ['error' => $e->getMessage()]);
            throw new CustomException('Gagal menghapus data Berita');
        }
    }

    public function KontenPublik($request): array
    {
        $limit = $request->input('limit', 10);

        $berita = Berita::select([
            'judul',
            'slug',
            'Kategori',
            'path_gambar',
            'published_at',
            'isi',
        ])
            ->where('tampilkan_publik', true)
            ->orderBy('published_at', 'desc')
            ->limit($limit)
            ->get()
            ->transform(function ($item) {
                return [
                    'judul'             => $item->judul,
                    'slug'              => $item->slug,
                    'kategori'          => $item->Kategori,
                    'gambar'            => $item->path_gambar ? url(Storage::url($item->path_gambar)) : null,
                    'tanggal'           => $item->published_at,
                    'deskripsi_singkat' => Str::limit(strip_tags($item->isi), 120, '...')
                ];
            });

        return [
            'message' => 'Data berita berhasil ditampilkan',
            'data'    => $berita
        ];
    }

    public function detailKonten($slug): array
    {
        $berita = Berita::where('slug', $slug)
            ->where('tampilkan_publik', true)
            ->select([
                'judul',
                'slug',
                'Kategori',
                'isi',
                'path_gambar',
                'published_at'
            ])
            ->first();

        if (!$berita) {
            throw new CustomException('Data berita tidak ditemukan');
        }

        if ($berita->path_gambar) {
            $berita->path_gambar = url(Storage::url($berita->path_gambar));
        }

        return [
            'message' => 'Detail berita berhasil ditampilkan',
            'data'    => $berita
        ];
    }
}

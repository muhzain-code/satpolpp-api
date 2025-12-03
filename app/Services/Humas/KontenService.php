<?php

namespace App\Services\Humas;

use App\Exceptions\CustomException;
use App\Models\Humas\Konten;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class KontenService
{
    // Role SuperAdmin / Humas
    // public function listKonten($currentPage, $perPage): array
    // {
    //     $konten = Konten::paginate($perPage, ['*'], 'page', $currentPage);

    //     return [
    //         'message' => 'data berhasil ditampilkan',
    //         'data'    => [
    //             'current_page'  => $konten->currentPage(),
    //             'per_page'      => $konten->perPage(),
    //             'total'         => $konten->total(),
    //             'last_page'     => $konten->lastPage(),
    //             'items'         => $konten->items(),
    //         ]
    //     ];
    // }

    public function listBerita($currentPage, $perPage): array
    {
        $berita = Konten::where('tipe', 'berita')
            ->latest()
            ->paginate($perPage, ['*'], 'page', $currentPage);
        $berita->getCollection()->transform(function ($item) {
            return [
                'id'        => $item->id,
                'tipe'      => $item->tipe,
                'judul'     => $item->judul,
                'slug'     => $item->slug,
                'isi' => $item->isi,
                'path_gambar'  => $item->path_gambar ? url(Storage::url($item->path_gambar)) : null,
                'tampilkan_publik' => (bool) $item->tampilkan_publik,
                'published_at'     => $item->published_at,
            ];
        });
        return [
            'message' => 'data berhasil ditampilkan',
            'data'    => [
                'current_page'  => $berita->currentPage(),
                'per_page'      => $berita->perPage(),
                'total'         => $berita->total(),
                'last_page'     => $berita->lastPage(),
                'items'         => $berita->items(),
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
                $path = $data['path_gambar']->store('konten', 'public');
            }

            $slug = Str::slug($data['judul']);
            $slug = $this->generateUniqueSlug($slug);

            $publishedAt = ($data['tampilkan_publik'] ?? false) ? now() : null;

            $konten = Konten::create([
                'tipe'              => 'berita',
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


    public function showBeritaById($id): array
    {
        $konten = Konten::where('id', $id)->where('tipe', 'berita')->first();


        if (!$konten) {
            throw new CustomException('Data tidak ditemukan');
        }

        return [
            'message' => 'data berhasil ditampilkan',
            'data'    => $konten
        ];
    }

    public function updateBeritaById(array $data, $id): array
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
                'tipe'              => 'berita',
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


    public function listAgenda($currentPage, $perPage, $keyword = null): array
    {
        $query = Konten::where('tipe', 'agenda')->latest();

        if (!empty($keyword)) {
            $query->where('judul', 'like', '%' . $keyword . '%');
        }

        $agenda = $query->paginate($perPage, ['*'], 'page', $currentPage)
            ->through(function ($item) {
                $tanggal = $item->tanggal_kegiatan ? Carbon::parse($item->tanggal_kegiatan)->format('d-m-Y') : '-';

                $jamMulai = $item->waktu_mulai ? Carbon::parse($item->waktu_mulai)->format('H:i') : '-';
                $jamSelesai = $item->waktu_selesai ? Carbon::parse($item->waktu_selesai)->format('H:i') : '-';

                return [
                    'id'             => $item->id,
                    'judul'          => $item->judul,
                    'slug'           => $item->slug,
                    'lokasi'         => $item->lokasi,
                    'tanggal'        => $tanggal,
                    'waktu'          => $jamMulai . ' s/d ' . $jamSelesai,
                    'jam_mulai'      => $jamMulai,
                    'jam_selesai'    => $jamSelesai,
                    'tampilkan_publik' => (bool) $item->tampilkan_publik,
                ];
            });

        return [
            'message' => 'Data agenda berhasil ditampilkan',
            'data'    => [
                'current_page' => $agenda->currentPage(),
                'per_page'     => $agenda->perPage(),
                'total'        => $agenda->total(),
                'last_page'    => $agenda->lastPage(),
                'items'        => $agenda->items(),
            ]
        ];
    }

    public function createAgenda(array $data): array
    {
        DB::beginTransaction();

        try {
            $slug = $this->generateUniqueSlug(Str::slug($data['judul']));
            $isPublic = $data['tampilkan_publik'] ?? false;
            $publishedAt = $isPublic ? now() : null;

            $konten = Konten::create([
                'tipe'             => 'agenda',
                'judul'            => $data['judul'],
                'slug'             => $slug,
                'isi'              => $data['isi'] ?? null,

                'lokasi'           => $data['lokasi'],
                'tanggal_kegiatan' => $data['tanggal_kegiatan'],
                'waktu_mulai'      => $data['waktu_mulai'],
                'waktu_selesai'    => $data['waktu_selesai'] ?? null,

                'tampilkan_publik' => $isPublic,
                'published_at'     => $publishedAt,
                'created_by'       => Auth::id(),
            ]);

            DB::commit();

            return [
                'message' => 'Berhasil menambahkan Agenda',
                'data'    => $konten
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error createAgenda: ' . $e->getMessage());
            throw new CustomException('Gagal menambahkan data Agenda');
        }
    }

    public function updateAgendaById(array $data, $id): array
    {
        DB::beginTransaction();

        try {
            $konten = Konten::where('id', $id)->where('tipe', 'agenda')->first();

            if (!$konten) {
                throw new CustomException('Data agenda tidak ditemukan');
            }

            $newSlug = $konten->slug;
            if ($konten->judul !== $data['judul']) {
                $newSlug = $this->generateUniqueSlug(Str::slug($data['judul']), $konten->id);
            }

            $isPublic = $data['tampilkan_publik'] ?? false;
            $publishedAt = $konten->published_at;

            if (!$konten->tampilkan_publik && $isPublic) {
                $publishedAt = now();
            }

            $konten->update([
                'judul'            => $data['judul'],
                'slug'             => $newSlug,
                'isi'              => $data['isi'] ?? $konten->isi,

                'lokasi'           => $data['lokasi'],
                'tanggal_kegiatan' => $data['tanggal_kegiatan'],
                'waktu_mulai'      => $data['waktu_mulai'],
                'waktu_selesai'    => $data['waktu_selesai'],

                'tampilkan_publik' => $isPublic,
                'published_at'     => $publishedAt,
                'updated_by'       => Auth::id(),
            ]);

            DB::commit();

            return [
                'message' => 'Data agenda berhasil diperbarui',
                'data'    => $konten
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error updateAgenda: ' . $e->getMessage());
            throw new CustomException('Gagal memperbarui data Agenda');
        }
    }

    public function showAgendaById($id): array
    {
        $konten = Konten::where('id', $id)->where('tipe', 'agenda')->first();

        if (!$konten) {
            throw new CustomException('Data agenda tidak ditemukan');
        }

        return [
            'message' => 'Detail agenda berhasil ditampilkan',
            'data'    => $konten
        ];
    }

    public function listHimbauan($currentPage, $perPage, $search = null): array
    {
        $query = Konten::where('tipe', 'himbauan')->latest();

        if ($search) {
            $query->where('judul', 'like', '%' . $search . '%');
        }

        $himbauan = $query->paginate($perPage, ['*'], 'page', $currentPage)
            ->through(function ($item) {
                return [
                    'id'            => $item->id,
                    'judul'         => $item->judul,
                    'slug'          => $item->slug,
                    'isi'           => Str::limit(strip_tags($item->isi), 100),
                    'tampilkan_publik' => (bool) $item->tampilkan_publik,
                    'path_gambar'   => $item->path_gambar ? url(Storage::url($item->path_gambar)) : null,
                    'published_at'  => $item->published_at,
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

            $konten = Konten::create([
                'tipe'              => 'himbauan',
                'judul'             => $data['judul'],
                'slug'              => $slug,
                'isi'               => $data['isi'] ?? null,
                'path_gambar'       => $path,
                'tampilkan_publik'  => $data['tampilkan_publik'] ?? true,
                'published_at'      => $publishedAt,
                'created_by'        => $userId,
            ]);

            DB::commit();

            return [
                'message' => 'Berhasil menambahkan Himbauan',
                'data'    => $konten
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Gagal tambah himbauan', ['error' => $e->getMessage()]);
            throw new CustomException('Gagal menambahkan data Himbauan');
        }
    }

    public function showHimbauanById($id): array
    {
        $konten = Konten::where('id', $id)->where('tipe', 'himbauan')->first();

        if (!$konten) {
            throw new CustomException('Data himbauan tidak ditemukan');
        }

        return [
            'message' => 'Detail himbauan berhasil ditampilkan',
            'data'    => $konten
        ];
    }

    public function updateHimbauanById(array $data, $id): array
    {
        DB::beginTransaction();

        try {
            $userId = Auth::id();
            $konten = Konten::where('id', $id)->where('tipe', 'himbauan')->first();

            if (!$konten) {
                throw new CustomException('Data himbauan tidak ditemukan');
            }

            if (isset($data['path_gambar']) && $data['path_gambar'] instanceof \Illuminate\Http\UploadedFile) {
                if ($konten->path_gambar && Storage::disk('public')->exists($konten->path_gambar)) {
                    Storage::disk('public')->delete($konten->path_gambar);
                }
                $data['path_gambar'] = $data['path_gambar']->store('himbauan', 'public');
            } else {
                $data['path_gambar'] = $konten->path_gambar;
            }

            $publishedAt = $konten->published_at;
            if (!$konten->tampilkan_publik && ($data['tampilkan_publik'] ?? false)) {
                $publishedAt = now();
            }

            $newSlug = $konten->slug;
            if ($konten->judul !== $data['judul']) {
                $newSlug = Str::slug($data['judul']);
                $newSlug = $this->generateUniqueSlug($newSlug, $konten->id);
            }

            $konten->update([
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
                'message' => 'Data himbauan berhasil diperbarui',
                'data'    => $konten
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Gagal update himbauan', ['error' => $e->getMessage()]);
            throw new CustomException('Gagal memperbarui data Himbauan');
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

    public function KontenPublik($request): array
    {
        $limit = $request->input('limit', 10);

        $konten = Konten::select([
            'tipe',
            'judul',
            'slug',
            'path_gambar',
            'published_at',
            'isi',
        ])
            ->where('tipe', 'berita')
            ->where('tampilkan_publik', true)
            ->orderBy('published_at', 'desc')
            ->limit($limit)
            ->get()
            ->transform(function ($item) {
                return [
                    'judul'        => $item->judul,
                    'slug'         => $item->slug,
                    'gambar'       => $item->path_gambar ? Storage::url($item->path_gambar) : null,
                    'tanggal'      => $item->published_at,
                    'deskripsi_singkat' => Str::limit(strip_tags($item->isi), 120, '...')
                ];
            });

        return [
            'message' => 'data berhasil ditampilkan',
            'data'    => $konten
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

    public function agendaPublik($request): array
    {
        $limit = $request->input('limit', 10);

        $konten = Konten::select([
            'tipe',
            'judul',
            'lokasi',
            'tanggal_kegiatan',
            'waktu_mulai',
            'waktu_selesai',
            'tampilkan_publik',
        ])
            ->where('tipe', 'agenda')
            ->where('tampilkan_publik', true)
            ->whereDate('tanggal_kegiatan', '>=', now())
            ->orderBy('tanggal_kegiatan', 'asc')

            ->limit($limit)
            ->get()
            ->transform(function ($item) {
                return [
                    'judul'            => $item->judul,
                    'tipe'             => $item->tipe,
                    'tanggal_kegiatan'          => $item->tanggal_kegiatan,
                    'lokasi'           => $item->lokasi,
                    'waktu_mulai'        => $item->waktu_mulai ? Carbon::parse($item->waktu_mulai)->format('H:i') . ' WIB' : '-',
                    'waktu_selesai'      => $item->waktu_selesai ? Carbon::parse($item->waktu_selesai)->format('H:i') . ' WIB' : 'Selesai',
                    'tampilkan_publik' => $item->tampilkan_publik,
                ];
            });

        return [
            'message' => 'data berhasil ditampilkan',
            'data'    => $konten
        ];
    }

    public function himbauanPublik($request): array
    {
        $limit = $request->input('limit', 10);

        $konten = Konten::select([
            'tipe',
            'judul',
            'slug',
            'path_gambar',
            'published_at',
            'isi',
        ])
            ->where('tipe', 'himbauan')
            ->where('tampilkan_publik', true)
            ->orderBy('published_at', 'desc')
            ->limit($limit)
            ->get()
            ->transform(function ($item) {
                return [
                    'tipe'        => $item->tipe,
                    'judul'        => $item->judul,
                    'slug'         => $item->slug,
                    'gambar'       => $item->path_gambar ? Storage::url($item->path_gambar) : null,
                    'tanggal'      => $item->published_at,
                    'deskripsi_singkat' => Str::limit(strip_tags($item->isi), 120, '...')
                ];
            });

        return [
            'message' => 'data berhasil ditampilkan',
            'data'    => $konten
        ];
    }
}

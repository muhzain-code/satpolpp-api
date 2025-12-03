<?php

namespace App\Services\DokumenRegulasi;

use Exception;
use App\Exceptions\CustomException;
use App\Models\Anggota\Anggota;
use App\Models\DokumenRegulasi\KategoriRegulasi;
use App\Models\DokumenRegulasi\Regulasi;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class RegulasiService
{
    public function getall($filters, $perPage, $currentPage): array
    {
        $query = Regulasi::with('kategoriRegulasi');

        if (!empty($filters['keyword'])) {
            $keyword = $filters['keyword'];
            $query->where(function ($q) use ($keyword) {
                $q->where('judul', 'like', "%{$keyword}%")
                    ->orWhere('kode', 'like', "%{$keyword}%")
                    ->orWhere('ringkasan', 'like', "%{$keyword}%");
            });
        }

        if (!empty($filters['tahun'])) {
            $query->where('tahun', $filters['tahun']);
        }

        if (!empty($filters['kategori_regulasi_id'])) {
            $query->where('kategori_regulasi_id', $filters['kategori_regulasi_id']);
        }

        if (isset($filters['tampilkan_publik'])) {
            $query->where('tampilkan_publik', $filters['tampilkan_publik']);
        }

        $regulasi = $query->orderBy('tahun', 'desc')
            ->paginate($perPage, ['*'], 'page', $currentPage);

        $items = $regulasi->getCollection()->transform(function ($item) {
            return [
                'id' => $item->id,
                'kode' => $item->kode,
                'judul' => $item->judul,
                'tahun' => $item->tahun,
                'kategori_regulasi' => $item->kategoriRegulasi->nama ?? null,
                'ringkasan' => $item->ringkasan,
                'path_pdf' => $item->path_pdf ? Storage::url($item->path_pdf) : null,
                'aktif' => $item->aktif,
                'tampilkan_publik' => $item->tampilkan_publik,
            ];
        });

        return [
            'message' => 'Data berhasil diambil',
            'data' => [
                'current_page' => $regulasi->currentPage(),
                'per_page' => $regulasi->perPage(),
                'total' => $regulasi->total(),
                'last_page' => $regulasi->lastPage(),
                'items' => $items,
            ]
        ];
    }

    public function create(array $data): array
    {
        try {
            $data['created_by'] = Auth::id();

            $exists = Regulasi::where('kode', $data['kode'])->exists();

            if ($exists) {
                throw new CustomException('Kode regulasi tersebut sudah ada, tidak bisa dipakai kembali.');
            }

            if (isset($data['path_pdf']) && $data['path_pdf']->isValid()) {
                $path = $data['path_pdf']->store('regulasi', 'public');
                $data['path_pdf'] = $path;
            }

            $regulasi = Regulasi::create([
                'kode'                  => $data['kode'],
                'judul'                 => $data['judul'],
                'tahun'                 => $data['tahun'],
                'kategori_regulasi_id'  => $data['kategori_regulasi_id'],
                'ringkasan'             => $data['ringkasan'] ?? null,
                'path_pdf'              => $data['path_pdf'] ?? null,
                'aktif'                 => $data['aktif'],
                'tampilkan_publik'      => $data['tampilkan_publik'] ?? false,
                'created_by'            => $data['created_by'],
            ]);

            return [
                'message'   => 'Data berhasil ditambahkan',
                'data'      => $regulasi
            ];
        } catch (Exception $e) {
            Log::error('Gagal menambah data regulasi', [
                'message' => $e->getMessage()
            ]);

            throw new CustomException('Gagal menambah data regulasi', 422);
        }
    }

    public function getbyid($id): array
    {
        $regulasi = Regulasi::find($id);

        if (!$regulasi) {
            throw new CustomException('Regulasi tidak ditemukan', 404);
        }

        $result = [
            'id'                    => $regulasi->id,
            'kode'                  => $regulasi->kode,
            'judul'                 => $regulasi->judul,
            'tahun'                 => $regulasi->tahun,
            'kategori_regulasi_id'  => $regulasi->kategori_regulasi_id,
            'ringkasan'             => $regulasi->ringkasan,
            'path_pdf'              => $regulasi->path_pdf ? url(Storage::url($regulasi->path_pdf)) : null,
            'aktif'                 => $regulasi->aktif,
            'tampilkan_publik'      => $regulasi->tampilkan_publik,
        ];

        return [
            'message' => 'Data berhasil ditampilkan',
            'data' => $result
        ];
    }

    public function update($id, array $data): array
    {
        try {
            $data['updated_by'] = Auth::id();

            $regulasi = Regulasi::find($id);

            if (! $regulasi) {
                throw new CustomException('Data Regulasi tidak ditemukan', 404);
            }

            $codeExists = Regulasi::where('kode', $data['kode'])
                ->where('id', '!=', $id)
                ->exists();

            if ($codeExists) {
                throw new CustomException('Kode regulasi tersebut sudah ada, tidak bisa dipakai kembali.', 422);
            }

            if (isset($data['path_pdf']) && $data['path_pdf'] instanceof \Illuminate\Http\UploadedFile) {
                if ($regulasi->path_pdf && Storage::disk('public')->exists($regulasi->path_pdf)) {
                    Storage::disk('public')->delete($regulasi->path_pdf);
                }
                $data['path_pdf'] = $data['path_pdf']->store('regulasi', 'public');
            }

            $regulasi->update([
                'kode'                  => $data['kode'],
                'judul'                 => $data['judul'],
                'tahun'                 => $data['tahun'],
                'kategori_regulasi_id'  => $data['kategori_regulasi_id'],
                'ringkasan'             => $data['ringkasan'] ?? null,
                'path_pdf'              => $data['path_pdf'] ?? $regulasi->path_pdf,
                'aktif'                 => $data['aktif'],
                'tampilkan_publik'      => $data['tampilkan_publik'] ?? $regulasi->tampilkan_publik,
                'updated_by'            => $data['updated_by'],
            ]);

            return [
                'message' => 'Data regulasi berhasil diperbarui.',
                'data'    => $regulasi
            ];
        } catch (\Throwable $e) {
            Log::error('Gagal update data Regulasi', [
                'error' => $e->getMessage(),
            ]);

            throw new CustomException('Gagal memperbarui data regulasi', 422);
        }
    }

    public function delete($id): array
    {
        $regulasi = Regulasi::find($id);

        if (!$regulasi) {
            throw new CustomException('Data Regulasi tidak ditemukan', 422);
        }

        $regulasi->delete();
        return [
            'message' => 'data berhasil dihapus'
        ];
    }

    public function regulasiPublik($request): array
    {
        $limit = $request->input('limit', 10);
        $kategoriId = $request->input('kategori_regulasi_id');

        $query = Regulasi::with('kategoriRegulasi')
            ->select([
                'id',
                'kategori_regulasi_id',
                'judul',
                'path_pdf',
                'ringkasan',
                'created_at',
                'tahun',
            ])
            ->where('tampilkan_publik', true)
            ->where('aktif', true);

        $query->when($kategoriId, function ($q, $id) {
            return $q->where('kategori_regulasi_id', $id);
        });

        $konten = $query->limit($limit)
            ->get()
            ->transform(function ($item) {
                return [
                    'judul'             => $item->judul,
                    'kategori_regulasi' => $item->kategoriRegulasi->nama ?? null,
                    'path_pdf' => $item->path_pdf ? url(Storage::url($item->path_pdf)) : null,
                    'tahun'             => $item->tahun,
                    'ringkasan'         => $item->ringkasan,
                    'tanggal'           => $item->created_at->format('Y-m-d'),
                ];
            });

        return [
            'message' => 'data berhasil ditampilkan',
            'data'    => $konten
        ];
    }

    public function filteringRegulasi(): array
    {
        $data = KategoriRegulasi::whereHas('regulasi', function ($q) {
            $q->where('tampilkan_publik', true)
                ->where('aktif', true);
        })
            ->select('id', 'nama')
            ->get()
            ->transform(function ($item) {
                return [
                    'kategori_regulasi_id' => $item->id,
                    'kategori_regulasi'    => $item->nama,
                ];
            });

        return [
            'message' => 'list kategori untuk filter',
            'data'    => $data
        ];
    }

    // public function GetallProgress($perPage, $currentPage, $request): array
    // {
    //     $user = Auth::user();

    //     if (!$user->hasRole('super_admin') && !$user->hasRole('admin')) {
    //         throw new CustomException('Akses ditolak. Fitur khusus Admin.', 403);
    //     }

    //     $bulan = $request->bulan ?? now()->month;
    //     $tahun = $request->tahun ?? now()->year;
    //     $regulasiId = $request->regulasi_id;
    //     $unitId = $request->unit_id;
    //     $statusFilter = $request->status;

    //     if (!$regulasiId) {
    //         return [
    //             'message' => 'Silakan pilih regulasi terlebih dahulu untuk melihat progres.',
    //             'data' => [
    //                 'current_page' => 1,
    //                 'per_page' => $perPage,
    //                 'total' => 0,
    //                 'last_page' => 1,
    //                 'items' => []
    //             ]
    //         ];
    //     }

    //     $query = Anggota::query()->with(['unit', 'user']);

    //     if ($unitId) {
    //         $query->where('unit_id', $unitId);
    //     }

    //     $query->with(['user.kemajuanPembacaan' => function ($q) use ($bulan, $tahun, $regulasiId) {
    //         $q->where('bulan', $bulan)
    //             ->where('tahun', $tahun)
    //             ->where('regulasi_id', $regulasiId)
    //             ->with('regulasi');
    //     }]);

    //     if ($statusFilter) {
    //         if ($statusFilter === 'selesai') {
    //             $query->whereHas('user.kemajuanPembacaan', function ($q) use ($bulan, $tahun, $regulasiId) {
    //                 $q->where('bulan', $bulan)
    //                     ->where('tahun', $tahun)
    //                     ->where('regulasi_id', $regulasiId)
    //                     ->where('status', 'selesai');
    //             });
    //         } elseif ($statusFilter === 'sedang') {
    //             $query->whereHas('user.kemajuanPembacaan', function ($q) use ($bulan, $tahun, $regulasiId) {
    //                 $q->where('bulan', $bulan)
    //                     ->where('tahun', $tahun)
    //                     ->where('regulasi_id', $regulasiId)
    //                     ->where('status', 'sedang');
    //             });
    //         } elseif ($statusFilter === 'belum') {
    //             $query->where(function ($mainQ) use ($bulan, $tahun, $regulasiId) {
    //                 $mainQ->whereDoesntHave('user.kemajuanPembacaan', function ($q) use ($bulan, $tahun, $regulasiId) {
    //                     $q->where('bulan', $bulan)
    //                         ->where('tahun', $tahun)
    //                         ->where('regulasi_id', $regulasiId);
    //                 })
    //                     ->orWhereHas('user.kemajuanPembacaan', function ($q) use ($bulan, $tahun, $regulasiId) {
    //                         $q->where('bulan', $bulan)
    //                             ->where('tahun', $tahun)
    //                             ->where('regulasi_id', $regulasiId)
    //                             ->where('status', 'belum');
    //                     });
    //             });
    //         }
    //     }

    //     $anggotaList = $query->orderBy('nama', 'asc')
    //         ->paginate($perPage, ['*'], 'page', $currentPage);

    //     $anggotaList->getCollection()->transform(function ($anggota) use ($regulasiId) {

    //         $progresData = null;
    //         if ($anggota->user && $anggota->user->relationLoaded('kemajuanPembacaan')) {
    //             $progresData = $anggota->user->kemajuanPembacaan->first();
    //         }

    //         $statusBaca = $progresData ? $progresData->status : 'belum';

    //         $judulBuku = '-';
    //         if ($progresData && $progresData->regulasi) {
    //             $judulBuku = $progresData->regulasi->judul;
    //         } else {
    //             $judulBuku = "Belum Membaca Harap di baca terlebih dahulu";
    //         }

    //         return [
    //             'id_anggota'   => $anggota->id,
    //             'nama_anggota' => $anggota->nama,
    //             'unit'         => $anggota->unit->nama ?? '-',
    //             'jabatan'      => $anggota->jabatan->nama ?? '-',
    //             'foto_profil'  => $anggota->foto ? url(Storage::url($anggota->foto)) : null,

    //             'filter_info' => [
    //                 'regulasi_target_id' => $regulasiId,
    //                 'info_buku'          => $judulBuku,
    //                 'status_baca'        => $statusBaca,
    //                 'terakhir_dibaca'    => $progresData ? Carbon::parse($progresData->terakhir_dibaca)->format('d-m-Y H:i') : '-',
    //             ]
    //         ];
    //     });

    //     return [
    //         'message' => 'Data progres seluruh anggota berhasil ditampilkan',
    //         'data' => [
    //             'current_page' => $anggotaList->currentPage(),
    //             'per_page'     => $anggotaList->perPage(),
    //             'total'        => $anggotaList->total(),
    //             'last_page'    => $anggotaList->lastPage(),
    //             'items'        => $anggotaList->items()
    //         ]
    //     ];
    // }
}

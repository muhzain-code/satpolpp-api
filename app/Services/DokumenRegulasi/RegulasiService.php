<?php

namespace App\Services\DokumenRegulasi;

use Exception;
use App\Exceptions\CustomException;
use App\Models\DokumenRegulasi\Regulasi;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class RegulasiService
{
    public function getAll($perPage, $currentPage): array
    {
        $regulasi = Regulasi::paginate($perPage, ['*'], 'page', $currentPage);

        $regulasi->getCollection()->transform(function ($item) {
            return [
                'id'        => $item->id,
                'kode'      => $item->kode,
                'judul'     => $item->judul,
                'tahun'     => $item->tahun,
                'jenis'     => $item->jenis,
                'ringkasan' => $item->ringkasan,
                'path_pdf'  => $item->path_pdf ? url(Storage::url($item->path_pdf)) : null,
                'aktif'     => $item->aktif,
            ];
        });

        return [
            'message'       => 'Data berhasil diambil',
            'data'          => [
                'current_page'  => $regulasi->currentPage(),
                'per_page'      => $regulasi->perPage(),
                'total'         => $regulasi->total(),
                'last_page'     => $regulasi->lastPage(),
                'items'         => $regulasi->items(),
            ]
        ];
    }

    public function store(array $data): array
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
                'kode'      => $data['kode'],
                'judul'     => $data['judul'],
                'tahun'     => $data['tahun'],
                'jenis'     => $data['jenis'],
                'ringkasan' => $data['ringkasan'] ?? null,
                'path_pdf'  => $data['path_pdf'] ?? null,
                'aktif'     => $data['aktif']
            ]);

            return [
                'message'   => 'Data berhasil di tambahkan',
                'data'      => $regulasi
            ];
        } catch (Exception $e) {
            Log::error('Gagal menambah data regulasi', [
                'message' => $e->getMessage()
            ]);

            throw new CustomException('Gagal menambah data regulasi', 422);
        }
    }

    public function getByid($id): array
    {
        $regulasi = Regulasi::find($id);

        if (!$regulasi) {
            throw new CustomException('Regulasi tidak ditemukan', 404);
        }

        $regulasi = [
            'id'        => $regulasi->id,
            'kode'      => $regulasi->kode,
            'judul'     => $regulasi->judul,
            'tahun'     => $regulasi->tahun,
            'jenis'     => $regulasi->jenis,
            'ringkasan' => $regulasi->ringkasan,
            'path_pdf'  => $regulasi->path_pdf ? url(Storage::url($regulasi->path_pdf)) : null,
            'aktif'     => $regulasi->aktif,
        ];
        return [
            'message' => 'Data berhasil ditampilkan',
            'data' => $regulasi
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
                'kode'      => $data['kode'],
                'judul'     => $data['judul'],
                'tahun'     => $data['tahun'],
                'jenis'     => $data['jenis'],
                'ringkasan' => $data['ringkasan'] ?? null,
                'path_pdf'  => $data['path_pdf'] ?? $regulasi->path_pdf,
                'aktif'     => $data['aktif'],
                'updated_by' => $data['updated_by'],
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
}

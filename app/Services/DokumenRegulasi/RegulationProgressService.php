<?php

namespace App\Services\DokumenRegulasi;

use App\Exceptions\CustomException;
use App\Models\DokumenRegulasi\KemajuanPembacaan;
use App\Models\DokumenRegulasi\Penanda;
use App\Models\DokumenRegulasi\Regulasi;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class RegulationProgressService
{
    public function getProgress($perPage, $currentPage): array
    {
        $UserID = Auth::id();
        if (!$UserID) {
            throw new CustomException('User tidak ditemukan');
        }

        $currentMonth = now()->month;
        $currentYear = now()->year;

        $Regulasi = Regulasi::where('aktif', true)
            ->with([
                'kemajuanPembacaan' => function ($query) use ($UserID, $currentMonth, $currentYear) {
                    $query->where('user_id', $UserID)
                        ->where('bulan', $currentMonth)
                        ->where('tahun', $currentYear);
                }
            ])->paginate($perPage, ['*'], 'page', $currentPage);

        $Regulasi->getCollection()->transform(function ($item) {
            $progress = $item->kemajuanPembacaan->first();
            $status = $progress ? $progress->status : 'belum';
            $terakhir_dibaca = $progress ? $progress->terakhir_dibaca : null;
            return [
                'id'        => $item->id,
                'kode'      => $item->kode,
                'judul'     => $item->judul,
                'tahun'     => $item->tahun,
                'jenis'     => $item->jenis,
                'ringkasan' => $item->ringkasan,
                'path_pdf'  => $item->path_pdf ? url(Storage::url($item->path_pdf)) : null,
                'aktif'     => $item->aktif,
                'progress' => [
                    'status'          => $status,
                    'terakhir_dibaca' => $terakhir_dibaca
                        ? Carbon::parse($terakhir_dibaca)->toDateTimeString()
                        : null,
                ]
            ];
        });
        return [
            'message' => 'Anggota berhasil ditampilkan',
            'data' => [
                'current_page' => $Regulasi->currentPage(),
                'per_page' => $Regulasi->perPage(),
                'total' => $Regulasi->total(),
                'last_page' => $Regulasi->lastPage(),
                'items' => $Regulasi->items()
            ]
        ];
    }

    public function progress(array $data): array
    {
        try {
            $userId = Auth::id();
            if (!$userId) {
                throw new CustomException('User tidak ditemukan.');
            }

            $now = now();
            $month = $now->month;
            $year = $now->year;
            $monthName = $now->translatedFormat('F');

            $alreadyRead = KemajuanPembacaan::where([
                ['user_id', $userId],
                ['regulasi_id', $data['regulasi_id']],
                ['bulan', $month],
                ['tahun', $year],
            ])->exists();

            if ($alreadyRead) {
                throw new CustomException("Anda sudah menyelesaikan progres baca untuk regulasi ini pada bulan {$monthName} {$year}.");
            }

            $progress = KemajuanPembacaan::create([
                'user_id' => $userId,
                'regulasi_id' => $data['regulasi_id'],
                'bulan' => $month,
                'tahun' => $year,
                'status' => 'selesai',
                'terakhir_dibaca' => $now,
            ]);

            return [
                'message' => 'Data berhasil ditambahkan',
                'data' => $progress
            ];
        } catch (\Throwable $e) {
            Log::error('Gagal menambahkan data progres', [
                'error' => $e->getMessage(),
            ]);

            if ($e instanceof CustomException) {
                throw $e;
            }

            throw new CustomException('Gagal menambahkan data progres');
        }
    }

    public function GetPenanda($Id): array
    {
        $userId = Auth::id();
        if (!$userId) {
            throw new CustomException('User tidak ditemukan.');
        }

        $Penanda = Penanda::where([
            ['user_id', $userId],
            ['regulasi_id', $Id],
        ])->first();

        if (!$Penanda) {
            throw new CustomException('Data tidak ditemukan');
        }

        return [
            'message' => 'Data berhasil diambil',
            'data' => $Penanda
        ];
    }

    public function penanda(array $data): array
    {
        try {
            $userId = Auth::id();
            if (!$userId) {
                throw new CustomException('User tidak ditemukan.');
            }

            $alreadyMarked = Penanda::where([
                ['user_id', $userId],
                ['regulasi_id', $data['regulasi_id']],
            ])->exists();

            if ($alreadyMarked) {
                throw new CustomException('Regulasi ini sudah Anda tandai sebelumnya, sehingga tidak dapat ditandai ulang.');
            }

            $penanda = Penanda::create([
                'user_id' => $userId,
                'regulasi_id' => $data['regulasi_id'],
                'catatan' => $data['catatan'] ?? null,
                'created_by' => $userId,
            ]);

            return [
                'message' => 'Data berhasil ditambahkan',
                'data' => $penanda
            ];
        } catch (\Throwable $e) {
            Log::error('Gagal menambahkan penanda', [
                'error' => $e->getMessage(),
            ]);

            if ($e instanceof CustomException) {
                throw $e;
            }

            throw new CustomException('Gagal menambahkan penanda.');
        }
    }
    public function UpdatePenanda(array $data, $Id): array
    {
        try {
            $userId = Auth::id();

            if (!$userId) {
                throw new CustomException('User tidak ditemukan.');
            }

            $penanda = Penanda::where('id', $Id)
                ->where('user_id', $userId)
                ->first();

            if (!$penanda) {
                throw new CustomException('Data penanda tidak ditemukan atau tidak memiliki akses.');
            }

            $penanda->update([
                'catatan' => $data['catatan'] ?? $penanda->catatan,
                'updated_by' => $userId
            ]);

            $penanda->refresh();

            return [
                'message' => 'Data penanda berhasil diperbarui.',
                'data' => $penanda
            ];
        } catch (\Throwable $e) {
            Log::error('Gagal update data penanda', [
                'error' => $e->getMessage(),
                'id' => $Id,
                'user_id' => Auth::id(),
            ]);

            if ($e instanceof CustomException) {
                throw $e;
            }

            throw new CustomException('Terjadi kesalahan saat memperbarui data penanda.');
        }
    }

    public function destroyPenanda($Id): array
    {
        $userId = Auth::id();

        if (!$userId) {
            throw new CustomException('User tidak ditemukan.');
        }

        $penanda = Penanda::where('id', $Id)
            ->where('user_id', $userId)
            ->first();

        if (!$penanda) {
            throw new CustomException('Data penanda tidak ditemukan atau tidak memiliki akses.');
        }

        $penanda->delete();

        return [
            'message' => 'data berhasil dihapus'
        ];
    }
}

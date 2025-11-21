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

            $progress = KemajuanPembacaan::where([
                ['user_id', $userId],
                ['regulasi_id', $data['regulasi_id']],
                ['bulan', $month],
                ['tahun', $year],
            ])->first();

            if (!$progress) {
                throw new CustomException("Anda belum memulai membaca regulasi ini. Silakan buka/baca materi terlebih dahulu.");
            }

            if ($progress->status === 'selesai') {
                $monthName = $now->translatedFormat('F');
                throw new CustomException("Kewajiban membaca regulasi ini untuk periode {$monthName} {$year} sudah selesai (1x per bulan). Silakan baca lagi bulan depan.");
            }

            if ($progress->status !== 'sedang') {
                throw new CustomException("Gagal update: Status saat ini adalah '{$progress->status}', hanya status 'sedang' yang bisa diselesaikan.");
            }

            $progress->update([
                'status' => 'selesai',
                'terakhir_dibaca' => $now,
            ]);

            return [
                'message' => 'Selamat! Anda telah menyelesaikan progres membaca regulasi ini.',
                'data' => $progress
            ];
        } catch (\Throwable $e) {
            Log::error('Gagal update progres ke selesai', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id() ?? 'unknown',
                'regulasi_id' => $data['regulasi_id'] ?? 'unknown'
            ]);

            if ($e instanceof CustomException) {
                throw $e;
            }

            throw new CustomException('Gagal menyelesaikan progres baca.');
        }
    }

    public function progressmembaca(array $data): array
    {
        try {
            $userId = Auth::id();
            if (!$userId) {
                throw new CustomException('User tidak ditemukan.');
            }

            $now = now();
            $month = $now->month;
            $year = $now->year;

            $progress = KemajuanPembacaan::where([
                ['user_id', $userId],
                ['regulasi_id', $data['regulasi_id']],
                ['bulan', $month],
                ['tahun', $year],
            ])->first();

            if ($progress) {
                if ($progress->status === 'selesai') {
                    return [
                        'message' => 'Progres sudah selesai (tidak ada perubahan)',
                        'data' => $progress
                    ];
                }

                $progress->update([
                    'terakhir_dibaca' => $now
                ]);

                return [
                    'message' => 'Melanjutkan membaca',
                    'data' => $progress
                ];
            }

            $newProgress = KemajuanPembacaan::create([
                'user_id' => $userId,
                'regulasi_id' => $data['regulasi_id'],
                'bulan' => $month,
                'tahun' => $year,
                'status' => 'sedang',
                'terakhir_dibaca' => $now,
            ]);

            return [
                'message' => 'Mulai membaca',
                'data' => $newProgress
            ];
        } catch (\Throwable $e) {
            Log::error('Gagal memproses progres membaca', [
                'error' => $e->getMessage(),
            ]);

            if ($e instanceof CustomException) {
                throw $e;
            }

            throw new CustomException('Gagal memproses progres membaca');
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
                'pasal_atau_halaman' => $data['pasal_atau_halaman'] ?? null,
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
                'pasal_atau_halaman' => $data['pasal_atau_halaman'] ?? $penanda->pasal_atau_halaman,
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

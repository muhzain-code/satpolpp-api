<?php

namespace App\Services\ManajemenLaporan;

use App\Exceptions\CustomException;
use App\Models\Anggota\Anggota;
use App\Models\ManajemenLaporan\LaporanHarian;
use App\Models\ManajemenLaporan\LaporanLampiran;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class LampiranLaporanService
{
    public function getLaporanAnggota($perPage, $currentPage): array
    {
        $user = Auth::user();

        if (!$user) {
            throw new CustomException('Anda belum login, silakan login terlebih dahulu');
        }

        $anggota = $user->anggota;

        if (!$anggota) {
            throw new CustomException('Akun Anda tidak memiliki data anggota');
        }

        $lap = LaporanHarian::where('anggota_id', $anggota->id)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $currentPage);

        if (!$lap) {
            throw new CustomException('Data tidak ditemukan');
        }

        $lap->getCollection()->transform(function ($item) {
            return [
                'id' => $item->id,
                'jenis' => $item->jenis,
                'catatan' => $item->catatan,
                'lat' => $item->lat,
                'lng' => $item->lng,
                'status_validasi' => $item->status_validasi,
                'divalidasi_oleh' => Anggota::find($item->divalidasi_oleh)->nama ?? null,
                'created_at' => $item->created_at,
            ];
        });

        return [
            'message' => 'Data laporan anggota berhasil ditampilkan',
            'data' => [
                'current_page' => $lap->currentPage(),
                'per_page' => $lap->perPage(),
                'total' => $lap->total(),
                'last_page' => $lap->lastPage(),
                'items' => $lap->items(),
            ]
        ];
    }

    public function GetByidLapAnggota($id): array
    {
        $user = Auth::user();

        if (!$user) {
            throw new CustomException('Anda belum login, silakan login terlebih dahulu');
        }

        $anggota = $user->anggota;

        if (!$anggota) {
            throw new CustomException('Akun Anda tidak memiliki data anggota');
        }

        $lap = LaporanHarian::with('lampiran')
            ->where('id', $id)
            ->where('anggota_id', $anggota->id)
            ->first();

        if (!$lap) {
            throw new CustomException('Data tidak ditemukan');
        }

        $lap->lampiran->transform(function ($item) {
            $item->path_file = url(Storage::url($item->path_file));
            return $item;
        });

        return [
            'message' => 'Data berhasil diambil',
            'data' => $lap
        ];
    }

    public function StoreLapAnggota(array $data): array
    {
        DB::beginTransaction();

        try {
            $user = Auth::user();

            if (!$user) {
                throw new CustomException('Anda belum login, silakan login terlebih dahulu');
            }

            $anggota = $user->anggota;

            if (!$anggota) {
                throw new CustomException('Akun Anda tidak memiliki data anggota');
            }

            if ($anggota->status !== 'aktif') {
                throw new CustomException('Akun anggota Anda tidak aktif, tidak dapat membuat laporan');
            }

            $laporan = LaporanHarian::create([
                'anggota_id'        => $anggota->id,
                'jenis'             => $data['jenis'] ?? 'aman',
                'catatan'           => $data['catatan'] ?? null,
                'lat'               => $data['lat'] ?? null,
                'lng'               => $data['lng'] ?? null,
                'status_validasi'   => 'menunggu',
                'created_by'        => $user->id,
            ]);

            if (!empty($data['lampiran']) && is_array($data['lampiran'])) {
                foreach ($data['lampiran'] as $file) {
                    if ($file instanceof \Illuminate\Http\UploadedFile && $file->isValid()) {
                        $path = $file->store('laporan_harian', 'public');

                        LaporanLampiran::create([
                            'laporan_id' => $laporan->id,
                            'path_file'  => $path,
                            'nama_file'  => $file->getClientOriginalName(),
                            'jenis'      => str_contains($file->getMimeType(), 'video') ? 'video' : 'foto',
                        ]);
                    }
                }
            }

            DB::commit();
            return [
                'message' => 'Laporan harian berhasil ditambahkan',
                'data'    => $laporan->load('lampiran'),
            ];
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Gagal menambah laporan harian', [
                'message' => $e->getMessage(),
            ]);

            if ($e instanceof CustomException) {
                throw $e;
            }

            throw new CustomException('Gagal menambah laporan harian', 422);
        }
    }

    public function UpdateLapAnggota($Id, array $data)
    {
        DB::beginTransaction();

        try {
            $user = Auth::user();

            if (!$user) {
                throw new CustomException('Anda belum login, silakan login terlebih dahulu');
            }

            $anggota = $user->anggota;

            if (!$anggota) {
                throw new CustomException('Akun Anda tidak memiliki data anggota');
            }

            if ($anggota->status !== 'aktif') {
                throw new CustomException('Akun anggota Anda tidak aktif, tidak dapat memperbarui laporan');
            }

            $laporan = LaporanHarian::with('lampiran')
                ->where('id', $Id)
                ->where('anggota_id', $anggota->id)
                ->first();

            if (!$laporan) {
                throw new CustomException('Data laporan harian tidak ditemukan', 404);
            }

            if ($laporan->status_validasi !== 'menunggu') {
                throw new CustomException('Status validasi sudah berubah, laporan tidak bisa diupdate');
            }

            if (!empty($data['lampiran'])) {
                foreach ($laporan->lampiran as $lampiran) {
                    if (Storage::disk('public')->exists($lampiran->path_file)) {
                        Storage::disk('public')->delete($lampiran->path_file);
                    }
                    $lampiran->delete();
                }
            }

            $laporan->update([
                'jenis'      => $data['jenis'] ?? $laporan->jenis,
                'catatan'    => $data['catatan'] ?? $laporan->catatan,
                'lat'        => $data['lat'] ?? $laporan->lat,
                'lng'        => $data['lng'] ?? $laporan->lng,
            ]);

            if (!empty($data['lampiran'])) {
                foreach ($data['lampiran'] as $file) {
                    if ($file->isValid()) {
                        $path = $file->store('laporan_harian', 'public');

                        LaporanLampiran::create([
                            'laporan_id' => $laporan->id,
                            'path_file'  => $path,
                            'nama_file'  => $file->getClientOriginalName(),
                            'jenis'      => str_contains($file->getMimeType(), 'video') ? 'video' : 'foto'
                        ]);
                    }
                }
            }

            DB::commit();

            return [
                'message' => 'Data laporan harian berhasil diperbarui',
                'data'    => $laporan->load('lampiran')
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Gagal memperbarui laporan harian', [
                'error' => $e->getMessage(),
            ]);
            if ($e instanceof CustomException) {
                throw $e;
            }

            throw new CustomException('Gagal memperbarui laporan harian', 422);
        }
    }

    public function GetKomandanLaporan($perPage, $currentPage): array
    {
        $user = Auth::user();

        if (!$user) {
            throw new CustomException('Anda belum login, silakan login terlebih dahulu');
        }

        $komandan = $user->anggota;

        if (!$komandan) {
            throw new CustomException('Akun Anda tidak memiliki data anggota');
        }

        if (!$user->hasRole('komandan_regu')) {
            throw new CustomException('Anda tidak memiliki akses untuk fitur ini (khusus Komandan Regu)', 403);
        }

        if (!$komandan->unit_id) {
            throw new CustomException('Data anggota anda tidak memiliki Unit Regu, tidak bisa mengambil data laporan');
        }

        $lap = LaporanHarian::whereHas('anggota', function ($q) use ($komandan) {
            $q->where('unit_id', $komandan->unit_id);
        })
            ->with('anggota')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $currentPage);

        if ($lap->total() === 0) {
            throw new CustomException('Data tidak ditemukan');
        }


        $lap->getCollection()->transform(function ($item) {
            return [
                'id' => $item->id,
                'anggota_id' => $item->anggota->nama,
                'jenis' => $item->jenis,
                'catatan' => $item->catatan,
                'lat' => $item->lat,
                'lng' => $item->lng,
                'status_validasi' => $item->status_validasi,
                'divalidasi_oleh' => Anggota::find($item->divalidasi_oleh)->nama ?? null,
                'created_at' => $item->created_at,
            ];
        });

        return [
            'message' => 'Data laporan anggota berhasil ditampilkan',
            'data' => [
                'current_page' => $lap->currentPage(),
                'per_page' => $lap->perPage(),
                'total' => $lap->total(),
                'last_page' => $lap->lastPage(),
                'items' => $lap->items(),
            ]
        ];
    }

    public function AccBykomandan($Id, array $data): array
    {
        DB::beginTransaction();
        try {
            $user = Auth::user();

            if (!$user) {
                throw new CustomException('Anda belum login, silakan login terlebih dahulu');
            }

            if (!$user->hasRole('komandan_regu')) {
                throw new CustomException('Anda tidak memiliki akses untuk fitur ini (khusus Komandan Regu)', 403);
            }

            $komandan = $user->anggota;

            if (!$komandan) {
                throw new CustomException('Akun Anda tidak memiliki data anggota');
            }

            if (!$komandan->unit_id) {
                throw new CustomException('Anggota anda tidak memiliki Unit Regu, tidak bisa memvalidasi laporan');
            }

            $lap = LaporanHarian::whereHas('anggota', function ($q) use ($komandan) {
                $q->where('unit_id', $komandan->unit_id);
            })
                ->where('id', $Id)
                ->first();

            if (!$lap) {
                throw new CustomException('Data laporan tidak ditemukan');
            }

            if ($lap->status_validasi !== 'menunggu') {
                throw new CustomException('Status validasi sudah berubah, laporan tidak bisa divalidasi');
            }

            $lap->update([
                'status_validasi' => $data['status_validasi'],
                'divalidasi_oleh' => $komandan->id,
            ]);

            DB::commit();

            return [
                'message' => 'Data berhasil divalidasi',
                'data' => $lap->refresh(),
            ];
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Gagal Memvalidasi data', [
                'error' => $e->getMessage()
            ]);

            if ($e instanceof CustomException) {
                throw $e;
            }

            throw new CustomException('Gagal memvalidasi data');
        }
    }
}

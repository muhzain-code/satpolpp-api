<?php

namespace App\Services\ManajemenLaporan;

use App\Exceptions\CustomException;
use App\Models\ManajemenLaporan\LaporanHarian;
use App\Models\ManajemenLaporan\LaporanLampiran;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class LaporanHarianService
{
    public function index($perPage, $currentPage): array
    {
        $lap = LaporanHarian::with(['anggota' => function ($query) {
            $query->where('status', 'aktif');
        }])
            ->paginate($perPage, ['*'], 'page', $currentPage);

        $lap->getCollection()->transform(function ($item) {
            return [
                'id' => $item->id,
                'nama' => $item->anggota->nama ?? null,
                'jenis' => $item->jenis,
                'catatan' => $item->catatan,
                'lat' => $item->lat,
                'lng' => $item->lng,
                'status_validasi' => $item->status_validasi,
                'divalidasi_oleh' => $item->divalidasi_oleh,
            ];
        });
        return [
            'message' => 'Anggota berhasil ditampilkan',
            'data' => [
                'current_page' => $lap->currentPage(),
                'per_page' => $lap->perPage(),
                'total' => $lap->total(),
                'last_page' => $lap->lastPage(),
                'items' => $lap->items()
            ]
        ];
    }


    public function store(array $data): array
    {
        DB::beginTransaction();

        try {
            $data['created_by'] = Auth::id();

            $laporan = LaporanHarian::create([
                'anggota_id'        => $data['anggota_id'],
                'jenis'             => $data['jenis'] ?? 'aman',
                'catatan'           => $data['catatan'] ?? null,
                'lat'               => $data['lat'] ?? null,
                'lng'               => $data['lng'] ?? null,
                'status_validasi'   => 'menunggu',
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


    public function getById($id): array
    {
        $laporan = LaporanHarian::with('lampiran')->find($id);

        if (!$laporan) {
            throw new CustomException('Laporan harian tidak ditemukan', 404);
        }

        $laporan->lampiran->transform(function ($item) {
            $item->path_file = url(Storage::url($item->path_file));
            return $item;
        });

        return [
            'message' => 'Data berhasil ditampilkan',
            'data'    => $laporan
        ];
    }

    public function update($id, array $data): array
    {
        DB::beginTransaction();
        try {
            $laporan = LaporanHarian::with('lampiran')->find($id);

            if (!$laporan) {
                throw new CustomException('Data laporan harian tidak ditemukan', 404);
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
                'jenis'           => $data['jenis'] ?? $laporan->jenis,
                'catatan'         => $data['catatan'] ?? $laporan->catatan,
                'lat'             => $data['lat'] ?? $laporan->lat,
                'lng'             => $data['lng'] ?? $laporan->lng,
                'status_validasi' => $data['status_validasi'] ?? $laporan->status_validasi,
                'divalidasi_oleh' => $data['divalidasi_oleh'] ?? $laporan->divalidasi_oleh,
                'updated_at'      => now(),
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

    public function delete($id): array
    {
        $laporan = LaporanHarian::with('lampiran')->find($id);

        if (!$laporan) {
            throw new CustomException('Data laporan harian tidak ditemukan', 422);
        }

        foreach ($laporan->lampiran as $lampiran) {
            if (Storage::disk('public')->exists($lampiran->path_file)) {
                Storage::disk('public')->delete($lampiran->path_file);
            }
        }

        $laporan->delete();

        return [
            'message' => 'Data laporan harian berhasil dihapus'
        ];
    }
    public function getallLaporan($perPage, $currentPage, $request): array
    {
        $user = Auth::user();

        if (!$user->hasRole('super_admin')) {
            throw new CustomException('Akses ditolak. Khusus Super Admin.', 403);
        }

        $query = LaporanHarian::with(['anggota.unit', 'validator']);

        // 2. Terapkan Filter
        if ($request->has('unit_id') && $request->unit_id != null) {
            $query->whereHas('anggota', function ($q) use ($request) {
                $q->where('unit_id', $request->unit_id);
            });
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59'
            ]);
        }

        if ($request->has('jenis') && $request->jenis != null) {
            $query->where('jenis', $request->jenis);
        }

        if ($request->has('severity') && $request->severity != null) {
            $query->where('severity', $request->severity);
        }

        if ($request->has('status_validasi') && $request->status_validasi != null) {
            $query->where('status_validasi', $request->status_validasi);
        }

        if ($request->has('telah_dieskalasi') && $request->telah_dieskalasi !== null) {
            $val = filter_var($request->telah_dieskalasi, FILTER_VALIDATE_BOOLEAN);
            $query->where('telah_dieskalasi', $val);
        }

        $laporan = $query->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $currentPage);

        $laporan->getCollection()->transform(function ($item) {
            return [
                'id' => $item->id,
                'tanggal' => $item->created_at->format('d-m-Y H:i'),
                'anggota' => $item->anggota->nama ?? '-',
                'unit' => $item->anggota->unit->nama ?? '-',
                'jenis' => $item->jenis,
                'severity' => $item->severity,
                'lokasi' => $item->catatan,
                'status' => $item->status_validasi,
                'divalidasi_oleh' => $item->validator->nama ?? '-',
                'eskalasi' => $item->telah_dieskalasi ? 'Ya' : 'Tidak',
            ];
        });

        return [
            'message' => 'Data seluruh laporan berhasil ditampilkan',
            'data' => [
                'current_page' => $laporan->currentPage(),
                'per_page' => $laporan->perPage(),
                'total' => $laporan->total(),
                'last_page' => $laporan->lastPage(),
                'items' => $laporan->items()
            ]
        ];
    }
}

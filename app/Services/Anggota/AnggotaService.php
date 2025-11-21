<?php

namespace App\Services\Anggota;

use Exception;
use App\Models\Anggota\Anggota;
use Illuminate\Http\UploadedFile;
use App\Exceptions\CustomException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Services\NomorGeneratorService;
use Illuminate\Support\Facades\Storage;

class AnggotaService
{
    protected NomorGeneratorService $service;

    public function __construct(NomorGeneratorService $service)
    {
        $this->service = $service;
    }

    public function getAll($perPage, $currentPage): array
    {
        $anggota = Anggota::with('unit:id,nama', 'jabatan:id,nama')
            ->where('status', 'aktif')
            ->paginate($perPage, ['*'], 'page', $currentPage);

        $anggota->getCollection()->transform(function ($item) {
            return [
                'id' => $item->id,
                'kode_anggota' => $item->kode_anggota,
                'nama' => $item->nama,
                'jenis_kelamin' => $item->jenis_kelamin,
                'foto' => url(Storage::url($item->foto)),
                'unit' => $item->unit?->nama,
                'jabatan' => $item->jabatan?->nama
            ];
        });

        return [
            'message' => 'Anggota berhasil ditampilkan',
            'data' => [
                'current_page' => $anggota->currentPage(),
                'per_page' => $anggota->perPage(),
                'total' => $anggota->total(),
                'last_page' => $anggota->lastPage(),
                'items' => $anggota->items()
            ]
        ];
    }

    public function getById($id): array
    {
        $anggota = Anggota::with('unit:id,nama', 'jabatan:id,nama')
            ->where('id', $id)
            ->where('status', 'aktif')
            ->first();

        if (!$anggota) {
            throw new CustomException('Anggota tidak ditemukan', 404);
        }

        $anggota = [
            'id' => $anggota->id,
            'kode_anggota' => $anggota->kode_anggota,
            'nik' => $anggota->nik,
            'nama' => $anggota->nama,
            'jenis_kelamin' => $anggota->jenis_kelamin,
            'tempat_lahir' => $anggota->tempat_lahir,
            'tanggal_lahir' => $anggota->tanggal_lahir,
            'alamat' => $anggota->alamat,
            'foto' => url(Storage::url($anggota->foto)),
            'jabatan' => $anggota->jabatan?->nama,
            'unit' => $anggota->unit?->nama,
            'status' => $anggota->status,
        ];

        return [
            'message' => 'Detail anggota berhasil ditampilkan',
            'data' => $anggota
        ];
    }

    public function create(array $data): array
    {
        try {
            $data['created_by'] = Auth::id();
            $path = null;

            if (isset($data['foto']) && $data['foto'] instanceof UploadedFile) {
                $path = $data['foto']->store('anggota', 'public');
                $data['foto'] = $path;
            }

            $data['kode_anggota'] = $this->service->generateKodeAnggota();

            $anggota = Anggota::create($data);

            if (!$anggota) {
                if ($path) {
                    Storage::disk('public')->delete($path);
                }
                throw new CustomException('Gagal menambah anggota', 422);
            }

            return [
                'message' => 'Berhasil menambah anggota',
                'data' => $anggota,
            ];
        } catch (Exception $e) {
            if (!empty($path)) {
                Storage::disk('public')->delete($path);
            }

            Log::error('Gagal menambah anggota', [
                'error' => $e->getMessage(),
                'data' => $data ?? null,
            ]);

            throw new CustomException('Gagal menambah anggota', 422);
        }
    }


    public function update(array $data, $id): array
    {
        try {
            $anggota = Anggota::find($id);

            if (!$anggota) {
                throw new CustomException('Anggota tidak ditemukan', 404);
            }

            if ($anggota->status !== 'aktif') {
                throw new CustomException('Anggota tidak aktif', 422);
            }

            $data['updated_by'] = Auth::id();

            $oldFoto = $anggota->foto;
            $newFotoPath = null;

            if (isset($data['foto']) && $data['foto'] instanceof UploadedFile) {
                $newFotoPath = $data['foto']->store('anggota', 'public');
                $data['foto'] = $newFotoPath;
            } else {
                unset($data['foto']);
            }

            $updateSuccess = $anggota->update($data);

            if (!$updateSuccess) {
                if ($newFotoPath) {
                    Storage::disk('public')->delete($newFotoPath);
                }
                throw new CustomException('Gagal update anggota', 422);
            }

            if ($newFotoPath && $oldFoto && Storage::disk('public')->exists($oldFoto)) {
                Storage::disk('public')->delete($oldFoto);
            }

            return [
                'message' => 'Berhasil mengupdate anggota',
                'data' => $anggota->fresh(),
            ];
        } catch (Exception $e) {
            Log::error('Gagal mengupdate anggota', [
                'error' => $e->getMessage(),
                'id' => $id,
            ]);

            throw new CustomException('Gagal update anggota', 422);
        }
    }


    public function delete($id): array
    {
        $anggota = Anggota::find($id);

        if (!$anggota) {
            throw new CustomException('Anggota tidak ditemukan', 404);
        }

        $anggota->deleted_by = Auth::id();
        $anggota->save();
        $anggota->delete();

        return [
            'message' => 'Berhasil menghapus anggota'
        ];
    }
}

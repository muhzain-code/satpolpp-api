<?php

namespace App\Services\Anggota;

use App\Models\Anggota\Jabatan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class JabatanService
{
    public function getAll()
    {
        try {
            $jabatans = Jabatan::all();

            return [
                'status' => true,
                'message' => 'Data jabatan berhasil diambil',
                'data' => $jabatans
            ];
        } catch (\Exception $e) {
            Log::error('Gagal mengambil semua jabatan', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return [
                'status' => false,
                'message' => 'Terjadi kesalahan saat mengambil data jabatan',
                'data' => null
            ];
        }
    }

    public function getById($id)
    {
        try {
            $jabatan = Jabatan::find($id);
            if (!$jabatan) {
                return [
                    'status' => false,
                    'message' => 'Data jabatan tidak ditemukan',
                    'data' => null
                ];
            }

            return [
                'status' => true,
                'message' => 'Data jabatan berhasil ditemukan',
                'data' => $jabatan
            ];
        } catch (\Exception $e) {
            Log::error('Gagal mengambil jabatan berdasarkan ID', [
                'id' => $id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return [
                'status' => false,
                'message' => 'Terjadi kesalahan saat mengambil data jabatan',
                'data' => null
            ];
        }
    }

    public function create(array $data)
    {
        try {
            $data['created_by'] = Auth::id();
            $jabatan = Jabatan::create($data);

            return [
                'status' => true,
                'message' => 'Jabatan berhasil dibuat',
                'data' => $jabatan
            ];
        } catch (\Exception $e) {
            Log::error('Gagal membuat jabatan', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'data' => $data
            ]);

            return [
                'status' => false,
                'message' => 'Terjadi kesalahan saat membuat jabatan',
                'data' => null
            ];
        }
    }

    public function update($id, array $data)
    {
        try {
            $jabatan = Jabatan::find($id);
            if (!$jabatan) {
                return [
                    'status' => false,
                    'message' => 'Data jabatan tidak ditemukan',
                    'data' => null
                ];
            }

            $data['updated_by'] = Auth::id();
            $jabatan->update($data);

            return [
                'status' => true,
                'message' => 'Jabatan berhasil diperbarui',
                'data' => $jabatan
            ];
        } catch (\Exception $e) {
            Log::error('Gagal memperbarui jabatan', [
                'id' => $id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'data' => $data
            ]);

            return [
                'status' => false,
                'message' => 'Terjadi kesalahan saat memperbarui jabatan',
                'data' => null
            ];
        }
    }

    public function delete($id)
    {
        try {
            $jabatan = Jabatan::find($id);
            if (!$jabatan) {
                return [
                    'status' => false,
                    'message' => 'Data jabatan tidak ditemukan'
                ];
            }

            $jabatan->deleted_by = Auth::id();
            $jabatan->save();
            $jabatan->delete();

            return [
                'status' => true,
                'message' => 'Jabatan berhasil dihapus'
            ];
        } catch (\Exception $e) {
            Log::error('Gagal menghapus jabatan', [
                'id' => $id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return [
                'status' => false,
                'message' => 'Terjadi kesalahan saat menghapus jabatan'
            ];
        }
    }
}

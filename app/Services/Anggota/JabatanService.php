<?php

namespace App\Services\Anggota;

use App\Models\Anggota\Jabatan;

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
            return [
                'status' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
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
            return [
                'status' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    public function create(array $data)
    {
        try {
            $jabatan = Jabatan::create($data);
            return [
                'status' => true,
                'message' => 'Jabatan berhasil dibuat',
                'data' => $jabatan
            ];
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
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

            $jabatan->update($data);
            return [
                'status' => true,
                'message' => 'Jabatan berhasil diperbarui',
                'data' => $jabatan
            ];
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
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

            $jabatan->delete();
            return [
                'status' => true,
                'message' => 'Jabatan berhasil dihapus'
            ];
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ];
        }
    }
}

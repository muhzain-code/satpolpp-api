<?php

namespace App\Services\Operasi;

use App\Models\Operasi\Penugasan;
use Illuminate\Support\Facades\DB;
use App\Exceptions\CustomException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PenugasanService
{
    public function getAll($perPage, $currentPage, array $filters = []): array
    {
        $user = Auth::user();
        $query = Penugasan::with(['anggota', 'disposisi', 'operasi', 'creator'])
            ->orderBy('created_at', 'desc');

        // --- LOGIKA HAK AKSES (SCOPE) ---

        // 1. Jika Role Komandan Regu: Hanya lihat yang DIA buat
        if ($user->hasRole('komandan_regu')) {
            $query->where('created_by', $user->id);
        }

        // 2. Jika Role Anggota Regu: Hanya lihat jika DIA ditugaskan (anggota_id match)
        elseif ($user->hasRole('anggota_regu')) {
            // Pastikan User terhubung dengan data Anggota
            if (!$user->anggota) {
                throw new CustomException('User ini tidak terhubung dengan data anggota.');
            }
            $query->where('anggota_id', $user->anggota->id);
        }

        // --- FILTER PARAMETER ---

        if (!empty($filters['disposisi_id'])) {
            $query->where('disposisi_id', $filters['disposisi_id']);
        }

        if (!empty($filters['operasi_id'])) {
            $query->where('operasi_id', $filters['operasi_id']);
        }

        $penugasan = $query->paginate($perPage, ['*'], 'page', $currentPage);

        // Transformasi data
        $penugasan->getCollection()->transform(function ($item) {
            return [
                'id'            => $item->id,
                'jenis_tugas'   => $item->disposisi_id ? 'disposisi' : 'Operasi',
                'disposisi_id'  => $item->disposisi_id,
                'operasi_id'    => $item->operasi_id,
                'anggota_id'    => $item->anggota_id,
                'nama_anggota'  => $item->anggota->nama,
                'peran'         => $item->peran,
                'created_by'    => $item->creator?->name,
            ];
        });

        return [
            'message' => 'Daftar penugasan berhasil ditampilkan',
            'data'    => [
                'current_page' => $penugasan->currentPage(),
                'per_page'     => $penugasan->perPage(),
                'total'        => $penugasan->total(),
                'last_page'    => $penugasan->lastPage(),
                'items'        => $penugasan->items()
            ]
        ];
    }

    /**
     * Menampilkan detail satu penugasan (Show)
     * Juga diproteksi agar user tidak bisa tembak ID milik orang lain
     */
    public function getById($id): array
    {
        $user = Auth::user();

        // Mulai query
        $query = Penugasan::with(['anggota.unit', 'anggota.jabatan', 'disposisi', 'operasi'])
            ->where('id', $id);

        // --- LOGIKA HAK AKSES (SCOPE) ---
        // Kita pasang filter di query builder agar jika tidak berhak, resultnya null (404)

        if ($user->hasRole('komandan_regu')) {
            $query->where('created_by', $user->id);
        } elseif ($user->hasRole('anggota_regu')) {
            if ($user->anggota) {
                $query->where('anggota_id', $user->anggota->id);
            } else {
                // Force fail jika user anggota tapi tidak punya relasi profile anggota
                throw new CustomException('Profil anggota tidak ditemukan untuk user ini.');
            }
        }

        $penugasan = $query->first();

        if (!$penugasan) {
            // Jika data tidak ada ATAU tidak punya hak akses, lempar 404
            // Ini lebih aman daripada memberi tahu "Data ada tapi Anda tidak boleh akses"
            throw new CustomException('Data penugasan tidak ditemukan atau Anda tidak memiliki akses.');
        }

        // Transformasi single object
        $data = [
            'id'             => $penugasan->id,
            'disposisi_id'   => $penugasan->disposisi_id,
            'operasi_id'     => $penugasan->operasi_id,
            'peran'          => $penugasan->peran,
            'anggota' => [
                'id'            => $penugasan->anggota->id,
                'nama'          => $penugasan->anggota->nama,
                'kode_anggota'  => $penugasan->anggota->kode_anggota,
                'unit'          => $penugasan->anggota->unit?->nama,
                'jabatan'       => $penugasan->anggota->jabatan?->nama,
                'foto'          => $penugasan->anggota->foto ? url(Storage::url($penugasan->anggota->foto)) : null,
            ],
            'konteks_tugas' => $penugasan->disposisi_id
                ? ['tipe' => 'disposisi', 'detail' => $penugasan->disposisi]
                : ['tipe' => 'Operasi', 'detail' => $penugasan->operasi],
        ];

        return [
            'message' => 'Detail penugasan berhasil ditampilkan',
            'data'    => $data
        ];
    }

    public function listAnggotaPenugasan($id): array
    {
        $user = Auth::user();

        // Ambil data penugasan berdasarkan pengaduan_id
        $penugasan = Penugasan::with(['anggota.unit', 'anggota.jabatan'])
            ->where('pengaduan_id', $id)
            ->get();

        // Jika tidak ada data
        if ($penugasan->isEmpty()) {
            throw new CustomException('Data penugasan tidak ditemukan atau Anda tidak memiliki akses.');
        }

        // Transform hasil
        $data = $penugasan->transform(function ($item) {
            return [
                'id'            => $item->id,
                'anggota_id'    => $item->anggota->id ?? null,
                'nama'          => $item->anggota->nama ?? null,
                'kode_anggota'  => $item->anggota->kode_anggota ?? null,
                'unit'          => $item->anggota->unit->nama ?? null,
                'jabatan'       => $item->anggota->jabatan->nama ?? null,
            ];
        });

        return [
            'message' => 'Detail penugasan berhasil ditampilkan',
            'data'    => $data,
        ];
    }


    /**
     * Create Penugasan (Batch Insert)
     * Diperbaiki untuk menangani Unique Constraint dan Transaction
     */
    public function create(array $data)
    {
        DB::beginTransaction();

        try {
            $createdItems = [];

            // Validasi logic: Harus ada parent (disposisi ATAU Operasi)
            if (empty($data['disposisi_id']) && empty($data['operasi_id'])) {
                throw new CustomException('Penugasan harus memiliki disposisi ID atau Operasi ID', 400);
            }

            foreach ($data['anggota_id'] as $index => $anggotaId) {
                // Gunakan updateOrCreate untuk menghindari error Duplicate Entry
                // Jika anggota sudah ditugaskan di disposisi/operasi ini, update perannya saja.
                $record = Penugasan::updateOrCreate(
                    [
                        'disposisi_id' => $data['disposisi_id'] ?? null,
                        'operasi_id'   => $data['operasi_id'] ?? null,
                        'anggota_id'   => $anggotaId,
                    ],
                    [
                        'peran'      => $data['peran'][$index] ?? null,
                        'created_by' => Auth::id(),
                    ]
                );

                $createdItems[] = $record;
            }

            DB::commit();

            return [
                'message' => 'Penugasan berhasil dibuat',
                'data'    => $createdItems,
            ];
        } catch (\Throwable $e) {
            DB::rollBack();

            // Log error asli untuk developer
            Log::error('Error create penugasan: ' . $e->getMessage());

            // Lempar CustomException agar response seragam
            if ($e instanceof CustomException) {
                throw $e;
            }
            throw new CustomException('Gagal membuat penugasan', 500);
        }
    }

    /**
     * Update Penugasan
     * Diperbaiki untuk validasi konflik anggota
     */
    public function update(int $id, array $data)
    {
        DB::beginTransaction();

        try {
            $penugasan = Penugasan::find($id);

            if (!$penugasan) {
                throw new CustomException('Data penugasan tidak ditemukan', 404);
            }

            // Validasi Logic: Jika user mengganti anggota, pastikan anggota baru belum ada di tim yang sama
            if (isset($data['anggota_id']) && $data['anggota_id'] != $penugasan->anggota_id) {
                $isExist = Penugasan::where('anggota_id', $data['anggota_id'])
                    ->where(function ($q) use ($penugasan) {
                        $q->where('disposisi_id', $penugasan->disposisi_id)
                            ->orWhere('operasi_id', $penugasan->operasi_id);
                    })
                    ->where('id', '!=', $id) // Kecuali data ini sendiri
                    ->exists();

                if ($isExist) {
                    throw new CustomException('Anggota tersebut sudah ada dalam penugasan ini', 400);
                }
            }

            // Lakukan update
            $penugasan->update([
                'anggota_id'   => $data['anggota_id'] ?? $penugasan->anggota_id, // Ambil single value, bukan array[0]
                'peran'        => $data['peran'] ?? $penugasan->peran, // Ambil single value
                'disposisi_id' => $data['disposisi_id'] ?? $penugasan->disposisi_id,
                'operasi_id'   => $data['operasi_id'] ?? $penugasan->operasi_id,
            ]);

            DB::commit();

            return [
                'message' => 'Penugasan berhasil diperbarui',
                'data'    => $penugasan,
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error update penugasan: ' . $e->getMessage());

            if ($e instanceof CustomException) {
                throw $e;
            }
            throw new CustomException('Gagal memperbarui penugasan', 500);
        }
    }

    public function delete($id)
    {
        $penugasan = Penugasan::find($id);

        if (!$penugasan) {
            throw new CustomException('Data tidak ditemukan', 404);
        }

        $penugasan->delete();

        return ['message' => 'Penugasan berhasil dihapus'];
    }
}

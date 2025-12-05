<?php

namespace App\Services\Operasi;

use App\Exceptions\CustomException;
use App\Models\Operasi\Penugasan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PenugasanService
{
    public function getAll($perPage, $currentPage, array $filters = []): array
    {
        $user = Auth::user();
        $query = Penugasan::with(['anggota', 'disposisi.pengaduan', 'operasi', 'creator'])
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
                'pengaduan_id'  => $item->disposisi?->pengaduan?->id,
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

        // Ambil data penugasan berdasarkan disposisi_id
        $penugasan = Penugasan::with(['anggota.unit', 'anggota.jabatan'])
            ->where('disposisi_id', $id)
            ->get();

        // Jika tidak ada data
        if ($penugasan->isEmpty()) {
            throw new CustomException('Data penugasan tidak ditemukan atau Anda tidak memiliki akses.');
        }

        // Transform hasil
        $data = $penugasan->transform(function ($item) {
            return [
                'id'            => $item->id,
                'anggota_id'    => $item->anggota_id ?? null,
                'peran'         => $item->peran,
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
                throw new CustomException('Penugasan harus memiliki disposisi ID atau Operasi ID');
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
        return DB::transaction(function () use ($id, $data) {
            try {
                // ------------------------------------------------------------------
                // 1. RESOLVE CONTEXT (Tentukan Parent: Disposisi atau Operasi)
                // ------------------------------------------------------------------

                // Cek apakah input memiliki ID parent. Jika tidak, ambil dari data existing ($id)
                $disposisiId = $data['disposisi_id'] ?? null;
                $operasiId   = $data['operasi_id'] ?? null;

                if (!$disposisiId && !$operasiId) {
                    $existing = Penugasan::findOrFail($id); // Fail fast jika id tidak ada
                    $disposisiId = $existing->disposisi_id;
                    $operasiId   = $existing->operasi_id;
                }

                // Validasi: Pastikan minimal salah satu parent terisi
                if (!$disposisiId && !$operasiId) {
                    throw new CustomException('Gagal mendeteksi konteks Disposisi atau Operasi.');
                }

                // ------------------------------------------------------------------
                // 2. PROSES UPDATE/CREATE (Upsert)
                // ------------------------------------------------------------------

                $processedAnggotaIds = [];
                $updatedRecords = [];

                foreach ($data['anggota_id'] as $index => $anggotaId) {

                    // Ambil peran secara aman (cegah error undefined array key)
                    $peran = $data['peran'][$index] ?? null;

                    // Gunakan updateOrCreate agar kode lebih deklaratif
                    $record = Penugasan::updateOrCreate(
                        [
                            // Kriteria pencarian (Composite Key)
                            'disposisi_id' => $disposisiId,
                            'operasi_id'   => $operasiId,
                            'anggota_id'   => $anggotaId,
                        ],
                        [
                            // Data yang diperbarui
                            'peran' => $peran,
                        ]
                    );

                    // Jika data baru dibuat (wasRecentlyCreated), isi created_by
                    // Kita update manual karena updateOrCreate param ke-2 akan menimpa created_by jika ditaruh di sana
                    if ($record->wasRecentlyCreated) {
                        $record->update(['created_by' => Auth::id()]);
                    }

                    $updatedRecords[] = $record;
                    $processedAnggotaIds[] = $anggotaId;
                }

                // ------------------------------------------------------------------
                // 3. SYNC (Hapus anggota yang tidak ada di request)
                // ------------------------------------------------------------------

                // Query dasar berdasarkan parent
                $query = Penugasan::query();

                if ($disposisiId) {
                    $query->where('disposisi_id', $disposisiId);
                } else {
                    $query->where('operasi_id', $operasiId);
                }

                // Hapus yang tidak termasuk dalam list yang baru diproses
                $query->whereNotIn('anggota_id', $processedAnggotaIds)->delete();

                return [
                    'message' => 'Daftar penugasan berhasil disinkronisasi',
                    'data'    => $updatedRecords,
                ];
            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                throw new CustomException('Data penugasan awal tidak ditemukan.', 404);
            } catch (\Throwable $e) {
                Log::error('Error sync penugasan: ' . $e->getMessage(), [
                    'stack' => $e->getTraceAsString()
                ]);

                // Rethrow jika itu custom exception kita, jika bukan buat generic error 500
                throw ($e instanceof CustomException)
                    ? $e
                    : new CustomException('Terjadi kesalahan saat memperbarui penugasan.', 500);
            }
        });
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

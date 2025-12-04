<?php

namespace App\Services\DokumenRegulasi;

use App\Exceptions\CustomException;
use App\Models\DokumenRegulasi\CatatanRegulasi;
use Illuminate\Database\Eloquent\Builder;
use App\Models\DokumenRegulasi\Regulasi;
use App\Models\DokumenRegulasi\RiwayatBaca;
use App\Models\DokumenRegulasi\StatistikPengguna;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RegulationProgressService
{

    public function listbacaan($perPage, $currentPage): array
    {
        $UserID = Auth::id();
        if (!$UserID) {
            throw new CustomException('User tidak ditemukan');
        }
        $today = Carbon::today()->toDateString();
        $Regulasi = Regulasi::where('aktif', true)
            ->withCount(['riwayatBaca as daily_completed_status' => function ($query) use ($UserID, $today) {
                $query->where('user_id', $UserID)
                    ->where('tanggal', $today)
                    ->where('status_selesai', true);
            }])
            ->with([
                'riwayatBaca' => function ($query) use ($UserID, $today) {
                    $query->where('user_id', $UserID)
                        ->where('tanggal', $today);
                },
                'kategoriRegulasi',
                'catatanRegulasi' => function ($query) use ($UserID) {
                    $query->where('user_id', $UserID);
                }
            ])
            ->orderBy('daily_completed_status', 'asc')
            ->paginate($perPage, ['*'], 'page', $currentPage);

        $Regulasi->getCollection()->transform(function ($item) {
            $riwayatHarian = $item->riwayatBaca->first();
            $isCompleted = $riwayatHarian ? (bool) $riwayatHarian->status_selesai : false;
            $statusLabel = $isCompleted ? 'completed' : 'not_started';

            return [
                'id'        => $item->id,
                'kode'      => $item->kode,
                'judul'     => $item->judul,
                'tahun'     => $item->tahun,
                'kategori'  => $item->kategoriRegulasi ? $item->kategoriRegulasi->nama : null,
                'ringkasan' => $item->ringkasan,
                'path_pdf'  => $item->path_pdf ? Storage::url($item->path_pdf) : null,
                'aktif'     => $item->aktif,
                'daily_progress' => [
                    'status_label'   => $statusLabel,
                    'is_completed'   => $isCompleted,
                    'durasi_detik'   => $riwayatHarian ? $riwayatHarian->durasi_detik : 0,
                    'terakhir_akses' => $riwayatHarian ? Carbon::parse($riwayatHarian->updated_at)->diffForHumans() : null,
                ],
                'catatan_regulasi' => $item->catatanRegulasi->map(function ($catatan) {
                    return [
                        'id'      => $catatan->id,
                        'halaman' => $catatan->halaman,
                        'type'    => $catatan->type,
                        'data'    => $catatan->data, 
                        'catatan' => $catatan->catatan,
                    ];
                }),
            ];
        });

        return [
            'message' => 'Jadwal review harian berhasil ditampilkan',
            'data' => [
                'current_page' => $Regulasi->currentPage(),
                'per_page'     => $Regulasi->perPage(),
                'total'        => $Regulasi->total(),
                'last_page'    => $Regulasi->lastPage(),
                'items'        => $Regulasi->items()
            ]
        ];
    }
    public function detailbacaan($id): array
    {
        $UserID = Auth::id();
        if (!$UserID) {
            throw new CustomException('User tidak ditemukan');
        }

        $today = Carbon::today()->toDateString();

        $regulasi = Regulasi::where('id', $id)
            ->where('aktif', true)
            ->with([
                'riwayatBaca' => function ($query) use ($UserID, $today) {
                    $query->where('user_id', $UserID)
                        ->where('tanggal', $today);
                },
                'kategoriRegulasi'
            ])
            ->first();

        if (!$regulasi) {
            throw new CustomException('Regulasi tidak ditemukan');
        }

        $riwayatHarian = $regulasi->riwayatBaca->first();
        $isCompleted = $riwayatHarian ? (bool) $riwayatHarian->status_selesai : false;

        if ($isCompleted) {
            $statusLabel = 'completed';
        } else {
            $statusLabel = 'not_started';
        }

        return [
            'message' => 'Detail regulasi berhasil ditampilkan',
            'data' => [
                'id'        => $regulasi->id,
                'kode'      => $regulasi->kode,
                'judul'     => $regulasi->judul,
                'tahun'     => $regulasi->tahun,
                'kategori'  => $regulasi->kategoriRegulasi ? $regulasi->kategoriRegulasi->nama : null,
                'ringkasan' => $regulasi->ringkasan,
                'path_pdf'  => $regulasi->path_pdf ? url(Storage::url($regulasi->path_pdf)) : null,
                'aktif'     => $regulasi->aktif,
                'daily_progress' => [
                    'status_label'   => $statusLabel,
                    'is_completed'   => $isCompleted,
                    'durasi_detik'   => $riwayatHarian ? $riwayatHarian->durasi_detik : 0,
                    'terakhir_akses' => $riwayatHarian ? Carbon::parse($riwayatHarian->updated_at)->diffForHumans() : null,
                ]
            ]
        ];
    }
    public function catatbacaan(array $data): array
    {
        $userId = Auth::id();
        if (!$userId) {
            throw new CustomException('User tidak ditemukan.');
        }

        if (!isset($data['durasi_detik'])) {
            throw new CustomException('Data durasi detik diperlukan.');
        }

        if ($data['durasi_detik'] < 180) {
            $menitKurang = ceil((180 - $data['durasi_detik']) / 60);
            throw new CustomException("Belum bisa menyelesaikan. Anda harus membaca minimal 3 menit. (Kurang sekitar {$menitKurang} menit lagi)");
        }

        $regulasiId = $data['regulasi_id'];
        $today = Carbon::today()->toDateString();
        $now = Carbon::now();

        return DB::transaction(function () use ($userId, $regulasiId, $data, $today, $now) {

            $riwayat = RiwayatBaca::where('user_id', $userId)
                ->where('regulasi_id', $regulasiId)
                ->where('tanggal', $today)
                ->first();

            $isNewRecord = false;
            $streakMessage = '';

            if (!$riwayat) {
                $isNewRecord = true;

                $riwayat = RiwayatBaca::create([
                    'user_id'        => $userId,
                    'regulasi_id'    => $regulasiId,
                    'tanggal'        => $today,
                    'durasi_detik'   => $data['durasi_detik'],
                    'status_selesai' => true,
                ]);

                $statistik = StatistikPengguna::where('user_id', $userId)->first();
                $yesterday = Carbon::yesterday()->toDateString();

                if (!$statistik) {
                    $statistik = StatistikPengguna::create([
                        'user_id' => $userId,
                        'streak_saat_ini' => 1,
                        'rekor_streak' => 1,
                        'tanggal_aktivitas_terakhir' => $today
                    ]);
                    $streakMessage = 'Streak dimulai! Ini adalah hari pertama streakmu.';
                } else {
                    if ($statistik->tanggal_aktivitas_terakhir === $today) {
                        $streakMessage = 'Progress tercatat! Kamu sudah mengamankan streak hari ini sebelumnya.';
                    } else {
                        if ($statistik->tanggal_aktivitas_terakhir === $yesterday) {
                            $statistik->streak_saat_ini += 1;
                            $streakMessage = 'Luar biasa! Streak bertambah menjadi ' . $statistik->streak_saat_ini . ' hari!';
                        } else {
                            $statistik->streak_saat_ini = 1;
                            $streakMessage = 'Streak dimulai kembali! Semangat membangun kebiasaan baru.';
                        }
                        if ($statistik->streak_saat_ini > $statistik->rekor_streak) {
                            $statistik->rekor_streak = $statistik->streak_saat_ini;
                            $streakMessage .= ' Rekor baru tercapai!';
                        }
                        $statistik->tanggal_aktivitas_terakhir = $today;
                        $statistik->save();
                    }
                }
            } else {
                $newDuration = $riwayat->durasi_detik;
                if ($data['durasi_detik'] > $riwayat->durasi_detik) {
                    $newDuration = $data['durasi_detik'];
                }

                $riwayat->update([
                    'status_selesai' => true,
                    'durasi_detik'   => $newDuration,
                    'updated_at'     => $now,
                ]);

                $streakMessage = 'Progress diperbarui. Teruslah membaca!';
            }

            return [
                'message' => $isNewRecord ? 'Selamat! Anda telah menyelesaikan bacaan ini. ' . $streakMessage : 'Data bacaan berhasil diperbarui.',
                'data' => [
                    'riwayat' => $riwayat,
                    'is_new_record' => $isNewRecord
                ]
            ];
        });
    }

    public function listtanda($perPage, $currentPage): array
    {
        $UserID = Auth::id();
        if (!$UserID) {
            throw new CustomException('User tidak di temukan');
        }

        $tanda = CatatanRegulasi::with('regulasi')
            ->where('user_id', $UserID)
            ->paginate($perPage, ['*'], 'page', $currentPage);

        $tanda->getCollection()->transform(function ($item) {
            return [
                'id'        => $item->id,
                'halaman'   => $item->halaman,
                'type'      => $item->type,
                'regulasi'  => $item->regulasi->judul ?? null,
            ];
        });

        return [
            'message' => 'data berhasil ditampilkan',
            'data'    => [
                'current_page'  => $tanda->currentPage(),
                'per_page'      => $tanda->perPage(),
                'total'         => $tanda->total(),
                'last_page'     => $tanda->lastPage(),
                'items'         => $tanda->items(),
            ]
        ];
    }

    public function detailPdf($id): array
    {
        $UserID = Auth::id();

        if (!$UserID) {
            throw new CustomException('User tidak ditemukan');
        }

        $tanda = CatatanRegulasi::with('regulasi')
            ->where('user_id', $UserID)
            ->where('regulasi_id', $id);

        $tanda->through(function ($item) {
            return [
                'id'        => $item->regulasi_id,
                'halaman'   => $item->halaman,
                'type'      => $item->type,
                'data'      => $item->data,
                'catatan'   => $item->catatan,
                'regulasi'  => $item->regulasi->judul ?? null,
            ];
        });

        return [
            'message' => 'data berhasil ditampilkan',
            'data'    => [
                'current_page' => $tanda->currentPage(),
                'per_page'     => $tanda->perPage(),
                'total'        => $tanda->total(),
                'last_page'    => $tanda->lastPage(),
                'items'        => $tanda->items(),
            ]
        ];
    }


    public function detailtanda($id): array
    {
        $UserID = Auth::id();
        if (!$UserID) {
            throw new CustomException('User tidak di temukan');
        }

        $tanda = CatatanRegulasi::with('regulasi')
            ->where('id', $id)
            ->where('user_id', $UserID)
            ->first();

        if (!$tanda) {
            throw new CustomException('data tidak ditemukan');
        }

        return [
            'message' => 'data berhasil di tampilkan',
            'data'    => [
                'id'            => $tanda->id,
                'halaman'       => $tanda->halaman,
                'type'          => $tanda->type,
                'regulasi_judul' => $tanda->regulasi->judul ?? null,
                'path_pdf' => $tanda->regulasi->path_pdf
                    ? url(Storage::url($tanda->regulasi->path_pdf))
                    : null,
                'data'          => $tanda->data,
            ]
        ];
    }

    public function buatPenandaPasal(array $data): array
    {
        try {
            $UserID = Auth::id();
            if (!$UserID) {
                throw new CustomException('User tidak di temukan');
            }

            $pasal = CatatanRegulasi::create([
                'user_id' => $UserID,
                'regulasi_id' => $data['regulasi_id'],
                'halaman' => $data['halaman'],
                'type' => 'highlight',
                'data' => $data['data'],
            ]);

            return [
                'message' => 'data berhasil di tambahkan',
                'data' => $pasal
            ];
        } catch (\Throwable $e) {
            Log::error('Gagal menambahkan penanda pasal', [
                'error' => $e->getMessage(),
            ]);
            if ($e instanceof CustomException) {
                throw $e;
            }
            throw new CustomException('Gagal menambahkan penanda pasal', 422);
        }
    }

    public function perbaruiPenandaPasal(int $id, array $data): array
    {
        try {
            $UserID = Auth::id();
            if (!$UserID) {
                throw new CustomException('User tidak ditemukan');
            }

            $pasal = CatatanRegulasi::where('id', $id)
                ->where('user_id', $UserID)
                ->first();

            if (!$pasal) {
                throw new CustomException('Data penanda tidak ditemukan atau Anda tidak memiliki akses', 404);
            }

            if ($pasal->type != 'highlight') {
                throw new CustomException('Data ini bukan highlight, tidak dapat diperbarui.');
            }

            $pasal->update([
                'halaman' => $data['halaman'] ?? $pasal->halaman,
                'data'    => $data['data'] ?? $pasal->data,
            ]);

            return [
                'message' => 'Data berhasil diperbarui',
                'data'    => $pasal
            ];
        } catch (\Throwable $e) {
            Log::error('Gagal memperbarui penanda pasal', [
                'id'    => $id,
                'error' => $e->getMessage(),
            ]);
            if ($e instanceof CustomException) {
                throw $e;
            }
            throw new CustomException('Gagal memperbarui penanda pasal');
        }
    }
    public function buatPenandaHalaman(array $data): array
    {
        try {
            $UserID = Auth::id();
            if (!$UserID) {
                throw new CustomException('User tidak di temukan');
            }

            $halaman = CatatanRegulasi::create([
                'user_id' => $UserID,
                'regulasi_id' => $data['regulasi_id'],
                'halaman' => $data['halaman'],
                'catatan' => $data['catatan'],
                'type' => 'note',
            ]);

            return [
                'message' => 'data berhasil di tambahkan',
                'data' => $halaman
            ];
        } catch (\Throwable $e) {
            Log::error('Gagal menambahkan penanda halaman', [
                'error' => $e->getMessage(),
            ]);
            if ($e instanceof CustomException) {
                throw $e;
            }
            throw new CustomException('Gagal menambahkan penanda halaman', 422);
        }
    }
    public function perbaruiPenandaHalaman(int $id, array $data): array
    {
        try {
            $UserID = Auth::id();
            if (!$UserID) {
                throw new CustomException('User tidak ditemukan');
            }

            $halaman = CatatanRegulasi::where('id', $id)
                ->where('user_id', $UserID)
                ->first();

            if (!$halaman) {
                throw new CustomException('Penanda halaman tidak ditemukan atau Anda tidak memiliki akses', 404);
            }

            if ($halaman->type !== 'note') {
                throw new CustomException('Data ini bukan catatan pribadi, tidak dapat diperbarui');
            }

            // Update data
            $halaman->update([
                'halaman' => $data['halaman'] ?? $halaman->halaman,
                'catatan'    => $data['catatan'] ?? $halaman->catatan,
            ]);

            return [
                'message' => 'Catatan pribadi berhasil diperbarui',
                'data'    => $halaman
            ];
        } catch (\Throwable $e) {

            Log::error('Gagal memperbarui penanda halaman', [
                'id'    => $id,
                'error' => $e->getMessage(),
            ]);

            if ($e instanceof CustomException) {
                throw $e;
            }

            throw new CustomException('Gagal memperbarui penanda halaman', 422);
        }
    }

    public function deletePenanda($id): array
    {
        try {
            $UserID = Auth::id();
            if (!$UserID) {
                throw new CustomException('User tidak ditemukan');
            }

            $penanda = CatatanRegulasi::where('id', $id)
                ->where('user_id', $UserID)
                ->first();

            if (!$penanda) {
                throw new CustomException('Penanda tidak ditemukan atau Anda tidak memiliki akses', 404);
            }

            $penanda->delete();

            return [
                'message' => 'Data berhasil dihapus'
            ];
        } catch (\Throwable $e) {

            Log::error('Gagal menghapus penanda', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            if ($e instanceof CustomException) {
                throw $e;
            }

            throw new CustomException('Gagal menghapus penanda', 422);
        }
    }

    public function monitoringLiterasi($perPage, $currentPage, array $filters): array
    {
        $filterType = $filters['filter_type'] ?? 'daily';
        $dateInput  = $filters['date'] ?? Carbon::today()->toDateString();
        $monthInput = $filters['month'] ?? Carbon::today()->format('Y-m');
        $status     = $filters['status'] ?? 'all';

        $timeConstraint = function ($query) use ($filterType, $dateInput, $monthInput) {
            $query->where('status_selesai', true);

            if ($filterType === 'monthly') {
                $year = substr($monthInput, 0, 4);
                $month = substr($monthInput, 5, 2);
                $query->whereYear('tanggal', $year)
                    ->whereMonth('tanggal', $month);
            } else {
                $query->whereDate('tanggal', $dateInput);
            }
        };
        $query = User::select('users.*')
            ->whereNotNull('anggota_id')
            ->with(['anggota.unit'])
            ->withCount(['riwayatBaca as filtered_read_count' => $timeConstraint])
            ->with(['riwayatBaca' => function ($q) use ($timeConstraint) {
                $timeConstraint($q);
                $q->with('regulasi:id,judul')
                    ->orderBy('updated_at', 'desc');
            }]);

        $currentUser = Auth::user();
        if (!$currentUser) {
            throw new CustomException('User Tidak Ditemukan');
        }
        $query->where('users.id', '!=', $currentUser->id);
        if ($currentUser->hasRole('komandan_regu')) {
            if ($currentUser->anggota && $currentUser->anggota->unit_id) {
                $unitId = $currentUser->anggota->unit_id;
                $query->whereHas('anggota', function (Builder $q) use ($unitId) {
                    $q->where('unit_id', $unitId);
                });
            } else {
                throw new CustomException('Anda Tidak memiliki UNIT Regu');
            }
        }
        if ($status === 'rajin') {
            $query->whereHas('riwayatBaca', $timeConstraint);
        } elseif ($status === 'tidak') {
            $query->whereDoesntHave('riwayatBaca', $timeConstraint);
        }

        $users = $query
            ->join('anggota', 'users.anggota_id', '=', 'anggota.id')
            ->orderByDesc('filtered_read_count')
            ->orderBy('anggota.nama', 'asc')
            ->paginate($perPage, ['*'], 'page', $currentPage);

        $users->getCollection()->transform(function ($user) {
            $readCount = $user->filtered_read_count;
            $isActive  = $readCount > 0;

            return [
                'user_id'       => $user->id,
                'nama'          => $user->anggota->nama ?? 'Tanpa Nama',
                'unit'          => $user->anggota->unit->nama ?? '-',
                'foto'          => $user->anggota->foto
                    ? url(Storage::url($user->anggota->foto))
                    : null,
                'stats' => [
                    'jumlah_buku'   => $readCount,
                    'status_label'  => $isActive ? "Sudah Menuntaskan {$readCount} Buku Regulasi" : 'Belum Menuntaskan Bacaan',
                ],
                'detail_regulasi' => $user->riwayatBaca->map(function ($log) {
                    return [
                        'judul_regulasi' => $log->regulasi->judul ?? '-',
                        'durasi_menit'   => $log->durasi_detik ? round($log->durasi_detik / 60) . ' Menit' : '< 1 Menit',
                        'waktu_baca'     => Carbon::parse($log->updated_at)->format('H:i'),
                        'tanggal'        => Carbon::parse($log->tanggal)->isoFormat('D MMM Y'),
                    ];
                }),
            ];
        });

        return [
            'message' => 'Data monitoring berhasil ditampilkan',
            'data' => [
                'current_page' => $users->currentPage(),
                'per_page'     => $users->perPage(),
                'total'        => $users->total(),
                'last_page'    => $users->lastPage(),
                'items'        => $users->items()
            ]
        ];
    }
}

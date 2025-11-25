<?php

namespace App\Services\Operasi;

use Barryvdh\DomPDF\PDF;
use App\Models\Operasi\Operasi;
use Illuminate\Support\Facades\DB;
use App\Exceptions\CustomException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Services\NomorGeneratorService;
use Illuminate\Support\Facades\Storage;
use App\Models\Operasi\OperasiPenugasan;

class OperasiService
{
    protected NomorGeneratorService $service;

    public function __construct(NomorGeneratorService $service)
    {
        $this->service = $service;
    }

    public function getAll($request)
    {
        $user = Auth::user();

        if ($user->hasRole('super_admin')) {
            $operasi = Operasi::with('pengaduan')->orderBy('created_at', 'desc');
        }

        if ($user->hasRole('komandan_regu')) {
            $operasi = Operasi::with('pengaduan')->where('created_by', $user->id)->orderBy('created_at', 'desc');
        }

        if ($request->filled('pengaduan_id')) {
            $operasi->where('pengaduan_id', $request->pengaduan_id);
        }

        if ($request->filled('mulai')) {
            $operasi->whereDate('mulai', '>=', $request->mulai);
        }

        if ($request->filled('selesai')) {
            $operasi->whereDate('selesai', '<=', $request->selesai);
        }

        if ($request->filled('keyword')) {
            $keyword = $request->keyword;

            $operasi->where(function ($q) use ($keyword) {
                $q->where('kode_operasi', 'like', "%{$keyword}%")
                    ->orWhere('judul', 'like', "%{$keyword}%");
            });
        }

        $operasi = $operasi->paginate(
            $request['per_page'] ?? 10,
            ['*'],
            'page',
            $request['page'] ?? 1
        );

        $operasi->getCollection()->transform(function ($item) {
            return [
                'id' => $item->id,
                'kode_operasi' => $item->kode_operasi,
                'nomor_surat_tugas' => $item->nomor_surat_tugas,
                'surat_tugas_pdf' => $item->surat_tugas_pdf ? url(Storage::url($item->surat_tugas_pdf)) : null,
                'pengaduan_id' => $item->pengaduan_id,
                'judul' => $item->judul,
                'uraian' => $item->uraian,
                'mulai' => $item->mulai,
                'selesai' => $item->selesai,
            ];
        });

        return [
            'message' => 'Operasi berhasil ditampilkan',
            'data' => [
                'current_page' => $operasi->currentPage(),
                'per_page'     => $operasi->perPage(),
                'total'        => $operasi->total(),
                'last_page'    => $operasi->lastPage(),
                'items'        => $operasi->items()
            ]
        ];
    }

    public function create(array $data): array
    {
        try {
            return DB::transaction(function () use ($data) {

                $kodeOperasi = $this->service->generateKodeOperasi();
                $nomorSuratTugas = $this->service->generateNomorSuratTugas();

                // -------------------------
                // 1. SIMPAN OPERASI
                // -------------------------
                $operasi = Operasi::create([
                    'kode_operasi'       => $kodeOperasi,
                    'nomor_surat_tugas'  => $nomorSuratTugas,
                    'tanggal_surat_tugas' => now()->toDateString(),
                    'pengaduan_id'       => $data['pengaduan_id'] ?? null,
                    'judul'              => $data['judul'],
                    'uraian'             => $data['uraian'] ?? null,
                    'mulai'              => $data['mulai'] ?? null,
                    'selesai'            => $data['selesai'] ?? null,
                    'created_by'         => Auth::id(),
                ]);

                if (!$operasi) {
                    throw new CustomException('Gagal membuat operasi', 422);
                }

                // -----------------------------------------
                // 2. SIMPAN OPERASI PENUGASAN (ANGGOTA)
                // -----------------------------------------
                $penugasanList = [];
                if (!empty($data['anggota'])) {
                    foreach ($data['anggota'] as $anggotaId) {
                        $penugasan = OperasiPenugasan::create([
                            'operasi_id' => $operasi->id,
                            'anggota_id' => $anggotaId,
                            'peran'      => $data['peran'][$anggotaId] ?? null,
                            'created_by' => Auth::id(),
                        ]);
                        $penugasanList[] = $penugasan;
                    }
                }

                // $operasi->load('createdBy.anggota.jabatan');

                // -----------------------------------------
                // 3. GENERATE PDF SURAT TUGAS (OTOMATIS)
                // -----------------------------------------
                $pdf = app('dompdf.wrapper')->loadView('pdf.surat_tugas', [
                    'operasi'   => $operasi,
                    'penugasan' => $penugasanList,
                    'tanggal'   => $operasi->tanggal_surat_tugas,
                ]);

                $filename = 'surat_tugas_' . $operasi->id . '.pdf';
                $path = 'surat_tugas/' . $filename;

                Storage::disk('public')->put($path, $pdf->output());

                // -----------------------------------------
                // 4. UPDATE PATH PDF DI TABEL OPERASI
                // -----------------------------------------
                $operasi->update([
                    'surat_tugas_pdf' => $path
                ]);

                return [
                    'success' => true,
                    'message' => 'Operasi, penugasan, dan surat tugas berhasil dibuat',
                    'data'    => $operasi
                ];
            });
        } catch (\Exception $e) {
            Log::error('Gagal membuat operasi', [
                'error' => $e->getMessage(),
            ]);

            throw new CustomException('Gagal membuat operasi', 422);
        }
    }


    public function getById($id)
    {
        $user = Auth::user();

        if ($user->hasRole('super_admin')) {
            $operasi = Operasi::find($id);
        }

        if ($user->hasRole('komandan_regu')) {
            $operasi = Operasi::where('created_by', $user->id)->find($id);
        }

        if (!$operasi) {
            throw new CustomException('Data operasi tidak ditemukan', 404);
        }

        $data =  [
            'id' => $operasi->id,
            'kode_operasi' => $operasi->kode_operasi,
            'nomor_surat_tugas' => $operasi->nomor_surat_tugas,
            'surat_tugas_pdf' => $operasi->surat_tugas_pdf ? url(Storage::url($operasi->surat_tugas_pdf)) : null,
            'pengaduan_id' => $operasi->pengaduan_id,
            'judul' => $operasi->judul,
            'uraian' => $operasi->uraian,
            'mulai' => $operasi->mulai,
            'selesai' => $operasi->selesai,
        ];

        return [
            'message' => 'Operasi berhasil ditemukan',
            'data' => $data
        ];
    }

    public function update($data, $id)
    {
        try {
            return DB::transaction(function () use ($data, $id) {

                $user = Auth::user();

                if ($user->hasRole('super_admin')) {
                    $operasi = Operasi::find($id);
                }

                if ($user->hasRole('komandan_regu')) {
                    $operasi = Operasi::where('created_by', $user->id)->find($id);
                }

                if (!$operasi) {
                    throw new CustomException('Data operasi tidak ditemukan', 404);
                }

                // -------------------------------------------------
                // 1. UPDATE DATA OPERASI
                // -------------------------------------------------
                $operasi->update([
                    'pengaduan_id'       => $data['pengaduan_id'] ?? $operasi->pengaduan_id,
                    'judul'              => $data['judul'] ?? $operasi->judul,
                    'uraian'             => $data['uraian'] ?? $operasi->uraian,
                    'mulai'              => $data['mulai'] ?? $operasi->mulai,
                    'selesai'            => $data['selesai'] ?? $operasi->selesai,
                    'updated_by'         => Auth::id(),
                ]);

                // -------------------------------------------------
                // 2. UPDATE OPERASI PENUGASAN (ANGGOTA)
                // -------------------------------------------------
                $penugasanList = [];

                if (isset($data['anggota']) && is_array($data['anggota'])) {

                    // hapus semua penugasan lama
                    OperasiPenugasan::where('operasi_id', $operasi->id)->delete();

                    // insert ulang anggota baru
                    foreach ($data['anggota'] as $anggotaId) {
                        $penugasan = OperasiPenugasan::create([
                            'operasi_id' => $operasi->id,
                            'anggota_id' => $anggotaId,
                            'peran'      => $data['peran'][$anggotaId] ?? null,
                            'created_by' => Auth::id(),
                        ]);

                        $penugasanList[] = $penugasan;
                    }
                }

                // -------------------------------------------------
                // 3. LOAD DATA LENGKAP UNTUK PDF
                // -------------------------------------------------
                $operasi->load('createdBy.anggota.jabatan');

                // -------------------------------------------------
                // 4. HAPUS PDF LAMA JIKA ADA
                // -------------------------------------------------
                if (!empty($operasi->surat_tugas_pdf)) {
                    if (Storage::disk('public')->exists($operasi->surat_tugas_pdf)) {
                        Storage::disk('public')->delete($operasi->surat_tugas_pdf);
                    }
                }

                // -------------------------------------------------
                // 5. GENERATE PDF BARU
                // -------------------------------------------------
                $pdf = app('dompdf.wrapper')->loadView('pdf.surat_tugas', [
                    'operasi'   => $operasi,
                    'penugasan' => $penugasanList,
                    'tanggal'   => $operasi->tanggal_surat_tugas,
                ]);

                $newFilename = 'surat_tugas_' . $operasi->id . '.pdf';
                $newPath = 'surat_tugas/' . $newFilename;

                Storage::disk('public')->put($newPath, $pdf->output());

                // -------------------------------------------------
                // 6. UPDATE PATH PDF DI DATABASE
                // -------------------------------------------------
                $operasi->update([
                    'surat_tugas_pdf' => $newPath
                ]);

                return [
                    'success' => true,
                    'message' => 'Operasi dan surat tugas berhasil diperbarui',
                    'data'    => $operasi
                ];
            });
        } catch (\Exception $e) {

            Log::error('Gagal memperbarui operasi', [
                'error' => $e->getMessage()
            ]);

            throw new CustomException('Gagal memperbarui data', 422);
        }
    }


    public function delete($id)
    {
        $user = Auth::user();

        if ($user->hasRole('super_admin')) {
            $operasi = Operasi::find($id);
        }

        if ($user->hasRole('komandan_regu')) {
            $operasi = Operasi::where('created_by', $user->id)->find($id);
        }

        if (!$operasi) {
            throw new CustomException('Data operasi tidak ditemukan', 404);
        }

        $operasi->update([
            'deleted_by' => Auth::id()
        ]);
        $operasi->delete();

        return [
            'message' => 'Operasi berhasil dihapus',
            'data' => null
        ];
    }

    public function getOperasiAnggota()
    {
        $anggotaId = Auth::user()->anggota_id;
        $operasi = OperasiPenugasan::with('operasiActive', 'creator', 'updater')->where('anggota_id', $anggotaId)->get();

        return [
            'message' => 'Data operasi anggota berhasil ditemukan',
            'data' => $operasi
        ];
    }
}

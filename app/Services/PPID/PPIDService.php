<?php

namespace App\Services\PPID;

use App\Exceptions\CustomException;
use App\Models\PPID\PPID;
use App\Services\NomorGeneratorService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PPIDService
{
    protected NomorGeneratorService $service;

    public function __construct(NomorGeneratorService $service)
    {
        $this->service = $service;
    }
    public function getAll($perPage, $currentPage): array
    {
        $PPID = PPID::orderByRaw("FIELD(status, 'diajukan', 'diproses', 'dijawab', 'ditolak')")
            ->paginate($perPage, ['*'], 'page', $currentPage);

        $PPID->getCollection()->transform(function ($item) {
            return [
                'id' => $item->id,
                'nomor_registrasi' => $item->nomor_registrasi,
                'nama_pemohon' => $item->nama_pemohon,
                'kontak_pemohon' => $item->kontak_pemohon,
                'informasi_diminta' => $item->informasi_diminta,
                'alasan_permintaan' => $item->alasan_permintaan,
                'status' => $item->status,
                'jawaban_ppid' => $item->jawaban_ppid,
                'file_jawaban' => $item->file_jawaban ? asset('storage/' . $item->file_jawaban) : null,
                'ditangani_oleh' => optional(optional($item->user)->anggota)->nama
                    ?? optional($item->user)->name
                    ?? 'Belum ditangani',

            ];
        });

        return [
            'message' => 'PPID berhasil ditampilkan',
            'data' => [
                'current_page' => $PPID->currentPage(),
                'per_page' => $PPID->perPage(),
                'total' => $PPID->total(),
                'last_page' => $PPID->lastPage(),
                'items' => $PPID->items()
            ]
        ];
    }

    public function permohonanPPID(array $data): array
    {
        DB::beginTransaction();
        try {

            $nomorRegistrasi = $this->service->generateNomorRegistrasiPPID();

            $PPID = PPID::create([
                'nomor_registrasi'   => $nomorRegistrasi,
                'nama_pemohon'       => $data['nama_pemohon'],
                'kontak_pemohon'     => $data['kontak_pemohon'],
                'informasi_diminta'  => $data['informasi_diminta'],
                'alasan_permintaan'  => $data['alasan_permintaan'],
                'status'             => 'diajukan',
            ]);

            DB::commit();
            return [
                'message' => 'Permohonan PPID berhasil diajukan',
                'data' => $PPID
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Gagal membuat permohonan PPID', [
                'error' => $e->getMessage(),
            ]);

            if ($e instanceof CustomException) {
                throw $e;
            }

            throw new CustomException('Terjadi kesalahan saat membuat permohonan PPID.');
        }
    }

    public function lacakPPID(array $data): array
    {
        DB::beginTransaction();
        try {
            $PPID = PPID::where('nomor_registrasi', $data['nomor_registrasi'])->first();

            if (!$PPID) {
                throw new CustomException('Data tidak ditemukan');
            }

            $catatan = match ($PPID->status) {
                'diajukan' => 'Permohonan Anda telah masuk dan menunggu diproses.',
                'diproses' => 'Permohonan Anda sedang dalam proses pemeriksaan.',
                'dijawab'  => $PPID->file_jawaban
                    ? 'Permohonan Anda telah dijawab. Silakan unduh file jawaban.'
                    : 'Permohonan Anda telah dijawab.',
                'ditolak'  => 'Permohonan Anda ditolak. Silakan baca alasan pada kolom jawaban.',
                default     => 'Status tidak diketahui.',
            };

            $response = [
                'nomor_registrasi' => $PPID->nomor_registrasi,
                'status' => $PPID->status,
                'jawaban_ppid' => $PPID->jawaban_ppid,
                'file_jawaban' => $PPID->file_jawaban ? asset('storage/' . $PPID->file_jawaban) : null,
                'catatan' => $catatan,
            ];
            DB::commit();

            return [
                'message' => 'Data berhasil ditampilkan',
                'data' => $response
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Gagal melacak permohonan PPID', [
                'error' => $e->getMessage(),
            ]);

            if ($e instanceof CustomException) {
                throw $e;
            }

            throw new CustomException('Terjadi kesalahan saat melacak permohonan PPID.');
        }
    }

    public function validasiPPID(array $data, $Id): array
    {
        DB::beginTransaction();
        try {
            $UserId = Auth::id();
            $PPID = PPID::find($Id);

            if (!$PPID) {
                throw new CustomException('Data tidak ditemukan');
            }

            if (in_array($PPID->status, ['dijawab', 'ditolak'])) {
                throw new CustomException("Tidak bisa divalidasi lagi, status sudah {$PPID->status}");
            }

            if ($data['status'] === 'ditolak' && !empty($data['file_jawaban'])) {
                throw new CustomException('File jawaban tidak boleh diisi jika status ditolak.');
            }

            if ($data['status'] === 'dijawab' && empty($data['jawaban_ppid'])) {
                throw new CustomException('Jawaban PPID wajib diisi jika status dijawab.');
            }
            if (!empty($data['file_jawaban'])) {
                $filePath = $data['file_jawaban']->store('ppid/jawaban', 'public');
                $data['file_jawaban'] = $filePath;
            } else {
                $data['file_jawaban'] = null;
            }

            $PPID->update([
                'status' => $data['status'],
                'jawaban_ppid' => $data['jawaban_ppid'],
                'file_jawaban' => $data['file_jawaban'] ?? null,
                'ditangani_oleh' => $UserId
            ]);

            DB::commit();
            return [
                'message' => 'Data berhasil divalidasi',
                'data' => $PPID
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Gagal membuat Validasi PPID', [
                'error' => $e->getMessage(),
            ]);

            if ($e instanceof CustomException) {
                throw $e;
            }

            throw new CustomException('Terjadi kesalahan saat membuat validasi PPID.');
        }
    }
}

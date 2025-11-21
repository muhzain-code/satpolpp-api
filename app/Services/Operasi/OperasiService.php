<?php

namespace App\Services\Operasi;

use App\Models\Operasi\Operasi;
use Illuminate\Support\Facades\DB;
use App\Exceptions\CustomException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Services\NomorGeneratorService;

class OperasiService
{
    protected NomorGeneratorService $service;

    public function __construct(NomorGeneratorService $service)
    {
        $this->service = $service;
    }

    public function getAll($filter)
    {
        $operasi = Operasi::with('pengaduan');

        if (isset($filter['pengaduan_id'])) {
            $operasi->where('pengaduan_id', $filter['pengaduan_id']);
        }

        if (isset($filter['mulai'])) {
            $operasi->whereDate('mulai', '>=', $filter['mulai']);
        }

        if (isset($filter['selesai'])) {
            $operasi->whereDate('selesai', '<=', $filter['selesai']);
        }

        if (isset($filter['keyword'])) {
            $operasi->where(function ($q) use ($filter) {
                $q->where('kode_operasi', 'like', '%' . $filter['keyword'] . '%')
                    ->orWhere('judul', 'like', '%' . $filter['keyword'] . '%');
            });
        }

        $operasi = $operasi->paginate(
            $filter['per_page'] ?? 10,
            ['*'],
            'page',
            $filter['page'] ?? 1
        );

        $operasi->getCollection()->transform(function ($item) {
            return [
                'id' => $item->id,
                'kode_operasi' => $item->kode_operasi,
                'nomor_surat_tugas' => $item->nomor_surat_tugas,
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
                

                $operasi = Operasi::create([
                    'kode_operasi'       => $kodeOperasi,
                    'nomor_surat_tugas' => $nomorSuratTugas,
                    'pengaduan_id'      => $data['pengaduan_id'] ?? null,
                    'jenis_operasi'      => $data['jenis_operasi'] ?? null,
                    'judul'             => $data['judul'],
                    'uraian'            => $data['uraian'] ?? null,
                    'mulai'             => $data['mulai'] ?? null,
                    'selesai'           => $data['selesai'] ?? null,
                    'created_by'        => Auth::id(),
                ]);

                if (!$operasi) {
                    throw new CustomException('Gagal membuat operasi', 422);
                }

                return [
                    'success' => true,
                    'message' => 'Operasi berhasil dibuat',
                    'data' => $operasi
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
        $operasi = Operasi::find($id);

        if (!$operasi) {
            throw new CustomException('Data operasi tidak ditemukan', 404);
        }

        return [
            'message' => 'Operasi berhasil ditemukan',
            'data' => $operasi
        ];
    }

    public function update($data, $id)
    {
        $operasi = Operasi::find($id);

        if (!$operasi) {
            throw new CustomException('Data operasi tidak ditemukan', 404);
        }
        
        $data['updated_by'] = Auth::id();
        $operasi->update($data);

        return [
            'message' => 'Operasi berhasil diperbarui',
            'data' => $operasi
        ];
    }

    public function delete($id)
    {
        $operasi = Operasi::find($id);

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
}

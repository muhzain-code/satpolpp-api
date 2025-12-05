<?php

namespace App\Services\Operasi;

use App\Models\Operasi\Penugasan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PenugasanService
{
    public function create(array $data)
    {
        DB::beginTransaction();

        try {
            $result = [];

            foreach ($data['anggota_id'] as $i => $anggota) {
                $record = Penugasan::create([
                    'pengaduan_id' => $data['pengaduan_id'] ?? null,
                    'operasi_id'   => $data['operasi_id'] ?? null,
                    'anggota_id'   => $anggota,
                    'peran'        => $data['peran'][$i] ?? null,
                    'created_by'   => Auth::id(),
                ]);

                $result[] = $record;
            }

            DB::commit();

            return [
                'message' => 'Penugasan berhasil dibuat.',
                'data'    => $result,
            ];
        } catch (\Throwable $e) {
            DB::rollBack();

            return [
                'message' => 'Terjadi kesalahan saat membuat penugasan.',
                'error'   => $e->getMessage(),
            ];
        }
    }

    public function update(int $id, array $data)
    {
        $penugasan = Penugasan::findOrFail($id);

        $penugasan->update([
            'pengaduan_id' => $data['pengaduan_id'] ?? null,
            'operasi_id'   => $data['operasi_id'] ?? null,
            'anggota_id'   => $data['anggota_id'][0],
            'peran'        => $data['peran'][0] ?? null,
        ]);

        return [
            'message' => 'Penugasan berhasil diperbarui.',
            'data'    => $penugasan,
        ];
    }
}

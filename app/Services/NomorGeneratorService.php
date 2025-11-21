<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use App\Models\SequenceCounter;
use Illuminate\Support\Facades\DB;

class NomorGeneratorService
{
    /**
     * Mengambil nomor urut berikutnya secara atomic + aman dari concurrency.
     */
    private function getNextSequenceValue(string $name, int $year, int $month): int
    {
        // Retry 5x jika deadlock (lebih aman untuk traffic tinggi)
        return DB::transaction(function () use ($name, $year, $month) {

            // Mengunci baris counter
            $counter = SequenceCounter::lockForUpdate()->firstOrCreate(
                compact('name', 'year', 'month'),
                ['count' => 0]
            );

            // Increment atomic
            $counter->increment('count');

            return $counter->count;
        }, attempts: 5);
    }


    /**
     * PGD-YYYY-XXXXX
     * Reset tahunan.
     */
    public function generateNomorTiket(): string
    {
        $now  = CarbonImmutable::now();
        $year = $now->year;

        $nomor = $this->getNextSequenceValue('pengaduan', $year, 0);
        $seq   = str_pad($nomor, 5, '0', STR_PAD_LEFT);

        return "PGD-{$year}-{$seq}";
    }


    /**
     * ST-YYYYMM-XXXX
     * Reset bulanan.
     */
    public function generateNomorSuratTugas(): string
    {
        $now   = CarbonImmutable::now();
        $year  = $now->year;
        $month = $now->month;

        $nomor = $this->getNextSequenceValue('surat_tugas', $year, $month);
        $seq   = str_pad($nomor, 4, '0', STR_PAD_LEFT);

        return "ST-{$now->format('Ym')}-{$seq}";
    }


    /**
     * PPID-YYYY-XXXXX
     * Reset tahunan.
     */
    public function generateNomorRegistrasiPPID(): string
    {
        $now  = CarbonImmutable::now();
        $year = $now->year;

        $nomor = $this->getNextSequenceValue('ppid', $year, 0);
        $seq   = str_pad($nomor, 5, '0', STR_PAD_LEFT);

        return "PPID-{$year}-{$seq}";
    }


    /**
     * OPR-YYYYMM-XXXX
     * Reset bulanan.
     * Digunakan untuk nomor operasi Satpol PP.
     */
    public function generateKodeOperasi(): string
    {
        $now   = CarbonImmutable::now();
        $year  = $now->year;
        $month = $now->month;

        $nomor = $this->getNextSequenceValue('operasi', $year, $month);
        $seq   = str_pad($nomor, 4, '0', STR_PAD_LEFT);

        return "OPR-{$now->format('Ym')}-{$seq}";
    }

    /**
     * AGT-YYYY-XXXXX
     * Reset tahunan.
     * Digunakan untuk kode anggota Satpol PP.
     */
    public function generateKodeAnggota(): string
    {
        $now  = CarbonImmutable::now();
        $year = $now->year;

        // Counter khusus anggota
        $nomor = $this->getNextSequenceValue('kode_anggota', $year, 0);

        // 5 digit sequence
        $seq = str_pad($nomor, 5, '0', STR_PAD_LEFT);

        return "AGT-{$year}-{$seq}";
    }

    public function generateNomorBAP(): string
    {
        $now   = CarbonImmutable::now();
        $year  = $now->year;
        $month = $now->month;

        // Counter untuk BAP
        $nomor = $this->getNextSequenceValue('bap', $year, $month);

        // 4 digit
        $seq = str_pad($nomor, 4, '0', STR_PAD_LEFT);

        return "BAP-{$now->format('Ym')}-{$seq}";
    }
}

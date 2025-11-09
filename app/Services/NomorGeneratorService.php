<?php
// app/Services/NomorGeneratorService.php

namespace App\Services;

use App\Models\SequenceCounter;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class NomorGeneratorService
{
    /**
     * Fungsi inti untuk mendapatkan nomor urut berikutnya secara aman (atomic).
     *
     * @param string $name  Nama counter (cth: 'pengaduan')
     * @param int $year     Tahun
     * @param int $month    Bulan (0 untuk reset tahunan)
     * @return int          Nomor urut berikutnya
     */
    private function getNextSequenceValue(string $name, int $year, int $month): int
    {
        // Memulai transaksi database.
        // Coba ulang 3x jika terjadi deadlock.
        return DB::transaction(function () use ($name, $year, $month) {
            
            // 1. Ambil baris counter, atau buat baru jika tidak ada.
            // lockForUpdate() MENGUNCI baris ini.
            // Tidak ada proses lain yang bisa menyentuh baris ini
            // sampai transaksi kita selesai.
            $counter = SequenceCounter::lockForUpdate()->firstOrCreate(
                [
                    'name' => $name,
                    'year' => $year,
                    'month' => $month,
                ],
                ['count' => 0] // Nilai awal jika baru dibuat
            );

            // 2. Tambah nilai 'count' secara atomic.
            $counter->increment('count');

            // 3. Kembalikan nilai 'count' yang BARU.
            return $counter->count;

        }, 3);
    }

    /**
     * Membuat Nomor Tiket Pengaduan (PGD-YYYY-XXXXX)
     * Direset setiap TAHUN.
     */
    public function generateNomorTiket(): string
    {
        $prefix = "PGD";
        $now = Carbon::now();
        $year = $now->year;
        $month = 0; // 0 = Reset Tahunan
        $name = 'pengaduan';

        $nomorUrut = $this->getNextSequenceValue($name, $year, $month);
        $sequence = str_pad($nomorUrut, 5, '0', STR_PAD_LEFT);

        return sprintf('%s-%s-%s', $prefix, $year, $sequence);
    }

    /**
     * Membuat Nomor Surat Tugas (ST-YYYYMM-XXXX)
     * Direset setiap BULAN.
     */
    public function generateNomorSuratTugas(): string
    {
        $prefix = "ST";
        $now = Carbon::now();
        $year = $now->year;
        $month = $now->month; // Reset Bulanan
        $name = 'surat_tugas';
        
        $nomorUrut = $this->getNextSequenceValue($name, $year, $month);
        $sequence = str_pad($nomorUrut, 4, '0', STR_PAD_LEFT);

        // Format YYYYMM
        return sprintf('%s-%s-%s', $prefix, $now->format('Ym'), $sequence);
    }

    /**
     * Membuat Nomor Registrasi PPID (PPID-YYYY-XXXXX)
     * Direset setiap TAHUN.
     */
    public function generateNomorRegistrasiPPID(): string
    {
        $prefix = "PPID";
        $now = Carbon::now();
        $year = $now->year;
        $month = 0; // 0 = Reset Tahunan
        $name = 'ppid';

        $nomorUrut = $this->getNextSequenceValue($name, $year, $month);
        $sequence = str_pad($nomorUrut, 5, '0', STR_PAD_LEFT);

        return sprintf('%s-%s-%s', $prefix, $year, $sequence);
    }
}
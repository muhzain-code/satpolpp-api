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
}
// class NomorGeneratorService
// {
//     /**
//      * Fungsi inti untuk mendapatkan nomor urut berikutnya secara aman (atomic).
//      *
//      * @param string $name  Nama counter (cth: 'pengaduan')
//      * @param int $year     Tahun
//      * @param int $month    Bulan (0 untuk reset tahunan)
//      * @return int          Nomor urut berikutnya
//      */
//     private function getNextSequenceValue(string $name, int $year, int $month): int
//     {
//         // Memulai transaksi database.
//         // Coba ulang 3x jika terjadi deadlock.
//         return DB::transaction(function () use ($name, $year, $month) {

//             // 1. Ambil baris counter, atau buat baru jika tidak ada.
//             // lockForUpdate() MENGUNCI baris ini.
//             // Tidak ada proses lain yang bisa menyentuh baris ini
//             // sampai transaksi kita selesai.
//             $counter = SequenceCounter::lockForUpdate()->firstOrCreate(
//                 [
//                     'name' => $name,
//                     'year' => $year,
//                     'month' => $month,
//                 ],
//                 ['count' => 0] // Nilai awal jika baru dibuat
//             );

//             // 2. Tambah nilai 'count' secara atomic.
//             $counter->increment('count');

//             // 3. Kembalikan nilai 'count' yang BARU.
//             return $counter->count;
//         }, 3);
//     }

//     /**
//      * Membuat Nomor Tiket Pengaduan (PGD-YYYY-XXXXX)
//      * Direset setiap TAHUN.
//      */
//     public function generateNomorTiket(): string
//     {
//         $prefix = "PGD";
//         $now = Carbon::now();
//         $year = $now->year;
//         $month = 0; // 0 = Reset Tahunan
//         $name = 'pengaduan';

//         $nomorUrut = $this->getNextSequenceValue($name, $year, $month);
//         $sequence = str_pad($nomorUrut, 5, '0', STR_PAD_LEFT);

//         return sprintf('%s-%s-%s', $prefix, $year, $sequence);
//     }

//     /**
//      * Membuat Nomor Surat Tugas (ST-YYYYMM-XXXX)
//      * Direset setiap BULAN.
//      */
//     public function generateNomorSuratTugas(): string
//     {
//         $prefix = "ST";
//         $now = Carbon::now();
//         $year = $now->year;
//         $month = $now->month; // Reset Bulanan
//         $name = 'surat_tugas';

//         $nomorUrut = $this->getNextSequenceValue($name, $year, $month);
//         $sequence = str_pad($nomorUrut, 4, '0', STR_PAD_LEFT);

//         // Format YYYYMM
//         return sprintf('%s-%s-%s', $prefix, $now->format('Ym'), $sequence);
//     }

//     /**
//      * Membuat Nomor Registrasi PPID (PPID-YYYY-XXXXX)
//      * Direset setiap TAHUN.
//      */
//     public function generateNomorRegistrasiPPID(): string
//     {
//         $prefix = "PPID";
//         $now = Carbon::now();
//         $year = $now->year;
//         $month = 0; // 0 = Reset Tahunan
//         $name = 'ppid';

//         $nomorUrut = $this->getNextSequenceValue($name, $year, $month);
//         $sequence = str_pad($nomorUrut, 5, '0', STR_PAD_LEFT);

//         return sprintf('%s-%s-%s', $prefix, $year, $sequence);
//     }

//     /**
//      * Membuat Kode Operasi Satpol PP
//      * Format: OPR-YYYYMM-XXXX
//      * - Reset setiap bulan
//      * - Aman untuk concurrency
//      */
//     public function generateKodeOperasi(): string
//     {
//         $prefix = "OPR";
//         $now = Carbon::now();
//         $year = $now->year;
//         $month = $now->month; 
//         $name = 'operasi';

//         // Ambil nomor urut berikutnya secara atomic
//         $nomorUrut = $this->getNextSequenceValue($name, $year, $month);
//         $sequence = str_pad($nomorUrut, 4, '0', STR_PAD_LEFT);

//         // Gabungkan: OPR-202511-0001
//         return sprintf('%s-%s-%s', $prefix, $now->format('Ym'), $sequence);
//     }
// }

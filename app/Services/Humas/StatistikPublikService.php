<?php

namespace App\Services\Humas;

use App\Models\Anggota\Anggota;
use App\Models\Operasi\Operasi;
use App\Models\Pengaduan\Pengaduan;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StatistikPublikService
{
    public function getDashboardData()
    {
        $now = Carbon::now();
        $totalOperasi = Operasi::whereYear('created_at', $now->year)->count();
        $pengaduanDiterima = Pengaduan::where('status', 'diterima')->count();
        $pengaduanSelesai = Pengaduan::where('status', 'selesai')->count();
        $totalPengaduanUntukPersentase = Pengaduan::where('status', '!=', 'ditolak')->count();
        $persentaseSelesai = $totalPengaduanUntukPersentase > 0
            ? number_format(($pengaduanSelesai / $totalPengaduanUntukPersentase) * 100, 1)
            : 0;
        $personelAktif = Anggota::where('status', 'aktif')->count();

        $statistikOperasi = Operasi::query()
            ->select(
                'kategori_pengaduan.nama as kategori',
                DB::raw('count(operasi.id) as total')
            )
            ->join('pengaduan', 'operasi.pengaduan_id', '=', 'pengaduan.id')
            ->join('kategori_pengaduan', 'pengaduan.kategori_id', '=', 'kategori_pengaduan.id')
            ->where('operasi.created_at', '>=', $now->copy()->subMonths(6))
            ->whereNull('operasi.deleted_at')
            ->whereNull('pengaduan.deleted_at')
            ->groupBy('kategori_pengaduan.nama')
            ->orderByDesc('total')
            ->get();

        return [
            'ringkasan' => [
                'total_operasi' => $totalOperasi,
                'pengaduan_selesai' => $pengaduanSelesai,
                'pengaduan_diterima' => $pengaduanDiterima,
                'persentase_penyelesaian' => $persentaseSelesai . '%',
                'personel_aktif' => $personelAktif,
            ],
            'grafik' => $statistikOperasi,
            'rata_rata_respon' => $this->getAverageResponseTime() // <-- KOREKSI DI SINI
        ];
    }
    private function getAverageResponseTime(): string
    {
        // Hitung selisih menit antara diterima dan diproses
        // Hanya untuk data yang sudah diproses
        $avgMinutes = Pengaduan::whereNotNull('diterima_at')
            ->whereNotNull('diproses_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, diterima_at, diproses_at)) as avg_time')
            ->value('avg_time');

        if (!$avgMinutes) {
            return '0 Jam';
        }

        $hours = floor($avgMinutes / 60);
        $minutes = $avgMinutes % 60;

        return "{$hours} Jam {$minutes} Menit";
    }
    public function getStatistikBulanIni(): array
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth   = Carbon::now()->endOfMonth();

        $pengaduanMasuk = DB::table('pengaduan')
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->count();

        $operasiDilaksanakan = DB::table('operasi')
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->count();
        $penindakanBerhasil = DB::table('penindakan')
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->count();

        $pengaduanSelesai = DB::table('pengaduan')
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->where('status', 'selesai')
            ->count();

        // $tingkatKepuasan = 0;
        // if ($pengaduanMasuk > 0) {
        //     $tingkatKepuasan = round(($pengaduanSelesai / $pengaduanMasuk) * 100);
        // }

        return [
            'periode' => Carbon::now()->translatedFormat('F Y'),
            'statistik' => [
                'pengaduan_masuk'       => $pengaduanMasuk,
                'operasi_dilaksanakan'  => $operasiDilaksanakan,
                'penindakan_berhasil'   => $penindakanBerhasil,
                // 'tingkat_kepuasan'      => $tingkatKepuasan . '%'
            ]
        ];
    }
}

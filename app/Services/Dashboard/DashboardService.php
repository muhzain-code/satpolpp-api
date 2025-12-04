<?php

namespace App\Services\Dashboard;

use App\Models\Operasi\Operasi;
use Illuminate\Support\Facades\DB;
use App\Models\Pengaduan\Pengaduan;

class DashboardService
{
    /**
     * Mengambil semua statistik dashboard.
     *
     * @return array
     */
    public function getDashboardStats(): array
    {
        return [
            'status' => true,
            'message' => 'Data statistik berhasil diambil',
            'data' => [
                'statistik_pengaduan' => $this->getComplaintCounts(),
                'rata_rata_respon' => $this->getAverageResponseTime(),
                'operasi_per_kategori' => $this->getOperationsByCategory(),
                'operasi_per_wilayah' => $this->getOperationsByRegion(),
                'heatmap_insiden' => $this->getHeatmapData(),
            ]
        ];
    }

    /**
     * 1. Jumlah pengaduan berdasarkan status.
     */
    private function getComplaintCounts(): array
    {
        // Menggunakan select raw untuk performa lebih baik daripada count() berulang
        $stats = Pengaduan::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        // Ensure default values exist if 0
        return [
            'total_masuk' => array_sum($stats),
            'diterima' => $stats['diterima'] ?? 0,
            'diproses' => $stats['diproses'] ?? 0,
            'selesai' => $stats['selesai'] ?? 0,
            'ditolak' => $stats['ditolak'] ?? 0,
        ];
    }

    /**
     * 2. Rata-rata waktu tanggap (dari diterima_at ke diproses_at).
     * Output dalam satuan jam atau menit.
     */
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

    /**
     * 3a. Jumlah operasi per Kategori Pengaduan.
     * Join: Operasi -> Pengaduan -> Kategori
     */
    private function getOperationsByCategory(): array
    {
        return Operasi::join('pengaduan', 'operasi.pengaduan_id', '=', 'pengaduan.id')
            ->join('kategori_pengaduan', 'pengaduan.kategori_id', '=', 'kategori_pengaduan.id')
            ->select('kategori_pengaduan.nama', DB::raw('count(operasi.id) as total_operasi'))
            ->groupBy('kategori_pengaduan.nama')
            ->orderByDesc('total_operasi')
            ->get()
            ->toArray();
    }

    /**
     * 3b. Jumlah operasi per Wilayah (Kecamatan).
     * Join: Operasi -> Pengaduan -> Kecamatan
     */
    private function getOperationsByRegion(): array
    {
        return Operasi::join('pengaduan', 'operasi.pengaduan_id', '=', 'pengaduan.id')
            ->join('kecamatan', 'pengaduan.kecamatan_id', '=', 'kecamatan.id')
            ->select('kecamatan.nama_kecamatan', DB::raw('count(operasi.id) as total_operasi'))
            ->groupBy('kecamatan.nama_kecamatan')
            ->orderByDesc('total_operasi')
            ->get()
            ->toArray();
    }

    /**
     * 4. Data Heatmap (Lat, Lng, dan bobot/intensitas).
     */
    private function getHeatmapData(): array
    {
        // Hanya ambil pengaduan yang memiliki koordinat valid
        return Pengaduan::whereNotNull('lat')
            ->whereNotNull('lng')
            ->select(
                'lat', 
                'lng', 
                'id',
                // Opsional: Sertakan kategori untuk filter di frontend
                'kategori_id',
                'status' 
            )
            ->get()
            ->map(function($item) {
                return [
                    'lat' => (float) $item->lat,
                    'lng' => (float) $item->lng,
                    'weight' => 1, // Bisa disesuaikan jika ingin bobot berdasarkan status
                    'info' => "Tiket #{$item->id} ({$item->status})"
                ];
            })
            ->toArray();
    }
}
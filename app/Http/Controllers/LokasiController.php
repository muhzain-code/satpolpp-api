<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LokasiController extends Controller
{
    public function search(Request $request)
    {
        $search = $request->input('q');

        if (!$search) {
            return response()->json([]);
        }

        // --- QUERY HANYA DESA (Kab. Probolinggo) ---
        // Kita hapus bagian union kecamatan, fokus langsung ke tabel desa
        $results = DB::table('desa')
            ->join('kecamatan', 'desa.kecamatan_id', '=', 'kecamatan.id')
            ->join('kabupaten', 'kecamatan.kabupaten_id', '=', 'kabupaten.id')
            ->join('provinsi', 'kabupaten.provinsi_id', '=', 'provinsi.id')
            ->select(
                'desa.id as id',
                'desa.id as desa_id',
                'kecamatan.id as kecamatan_id',
                'kabupaten.id as kabupaten_id',
                'provinsi.id as provinsi_id',
                // Format teks lengkap: Desa X, Kec Y, Kab Z...
                DB::raw("CONCAT('Desa ', desa.nama_desa, ', Kec. ', kecamatan.nama_kecamatan, ', ', kabupaten.nama_kabupaten, ', ', provinsi.nama_provinsi) as text"),
                DB::raw("'desa' as type")
            )
            ->where('kabupaten.nama_kabupaten', 'KAB. PROBOLINGGO')
            ->where(function ($q) use ($search) {
                // Logika pencarian:
                // 1. Cocok dengan nama desa, ATAU
                // 2. Cocok dengan nama kecamatan (ini akan menampilkan semua desa di kecamatan tsb)
                $q->where('desa.nama_desa', 'LIKE', "%{$search}%")
                    ->orWhere('kecamatan.nama_kecamatan', 'LIKE', "%{$search}%");
            })
            // Prioritaskan yang nama desanya diawali kata kunci pencarian
            ->orderByRaw("CASE WHEN desa.nama_desa LIKE '{$search}%' THEN 0 ELSE 1 END")
            // Lalu urutkan berdasarkan nama desa secara alfabet
            ->orderBy('desa.nama_desa', 'asc')
            ->limit(20)
            ->get();

        return response()->json($results);
    }
}

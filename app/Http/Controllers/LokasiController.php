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

        // --- QUERY KECAMATAN (Kab. Probolinggo) ---
        $kecamatan = DB::table('kecamatan')
            ->join('kabupaten', 'kecamatan.kabupaten_id', '=', 'kabupaten.id')
            ->join('provinsi', 'kabupaten.provinsi_id', '=', 'provinsi.id')
            ->select(
                'kecamatan.id as id',
                DB::raw("NULL as desa_id"),
                'kecamatan.id as kecamatan_id',
                'kabupaten.id as kabupaten_id',
                'provinsi.id as provinsi_id',
                DB::raw("CONCAT('Kec. ', kecamatan.nama_kecamatan, ', ', kabupaten.nama_kabupaten, ', ', provinsi.nama_provinsi) as text"),
                DB::raw("'kecamatan' as type"),
                DB::raw("3 as rank_level")
            )
            ->where('kabupaten.nama_kabupaten', 'KAB. PROBOLINGGO')
            ->where('kecamatan.nama_kecamatan', 'LIKE', "%{$search}%");

        // --- QUERY DESA (Kab. Probolinggo) ---
        $desa = DB::table('desa')
            ->join('kecamatan', 'desa.kecamatan_id', '=', 'kecamatan.id')
            ->join('kabupaten', 'kecamatan.kabupaten_id', '=', 'kabupaten.id')
            ->join('provinsi', 'kabupaten.provinsi_id', '=', 'provinsi.id')
            ->select(
                'desa.id as id',
                'desa.id as desa_id',
                'kecamatan.id as kecamatan_id',
                'kabupaten.id as kabupaten_id',
                'provinsi.id as provinsi_id',
                DB::raw("CONCAT('Desa ', desa.nama_desa, ', Kec. ', kecamatan.nama_kecamatan, ', ', kabupaten.nama_kabupaten, ', ', provinsi.nama_provinsi) as text"),
                DB::raw("'desa' as type"),
                DB::raw("4 as rank_level")
            )
            ->where('kabupaten.nama_kabupaten', 'KAB. PROBOLINGGO')
            ->where(function ($q) use ($search) {
                $q->where('desa.nama_desa', 'LIKE', "%{$search}%")
                    ->orWhere('kecamatan.nama_kecamatan', 'LIKE', "%{$search}%");
            });

        // gabungkan & urutkan
        $results = $kecamatan
            ->unionAll($desa)
            ->orderByRaw("CASE WHEN text LIKE '{$search}%' THEN 0 ELSE 1 END")
            ->orderBy('rank_level', 'asc')
            ->orderBy('text', 'asc')
            ->limit(20)
            ->get();

        return response()->json($results);
    }
}

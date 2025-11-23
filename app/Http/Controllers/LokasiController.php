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

    // --- QUERY 1: Level PROVINSI (Prioritas Tertinggi) ---
    // rank_level = 1
    $provinsi = DB::table('provinsi')
        ->select(
            'provinsi.id as id',
            DB::raw("NULL as desa_id"),
            DB::raw("NULL as kecamatan_id"),
            DB::raw("NULL as kabupaten_id"),
            'provinsi.id as provinsi_id',
            DB::raw("provinsi.nama_provinsi as text"),
            DB::raw("'provinsi' as type"),
            DB::raw("1 as rank_level") // Set 1 agar muncul paling atas
        )
        ->where('provinsi.nama_provinsi', 'LIKE', "%{$search}%");

    // --- QUERY 2: Level KABUPATEN ---
    // rank_level = 2
    $kabupaten = DB::table('kabupaten')
        ->join('provinsi', 'kabupaten.provinsi_id', '=', 'provinsi.id')
        ->select(
            'kabupaten.id as id',
            DB::raw("NULL as desa_id"),
            DB::raw("NULL as kecamatan_id"),
            'kabupaten.id as kabupaten_id',
            'provinsi.id as provinsi_id',
            DB::raw("CONCAT(kabupaten.nama_kabupaten, ', ', provinsi.nama_provinsi) as text"),
            DB::raw("'kabupaten' as type"),
            DB::raw("2 as rank_level")
        )
        ->where('kabupaten.nama_kabupaten', 'LIKE', "%{$search}%");

    // --- QUERY 3: Level KECAMATAN ---
    // rank_level = 3
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
        ->where('kecamatan.nama_kecamatan', 'LIKE', "%{$search}%");

    // --- QUERY 4: Level DESA (Prioritas Terendah) ---
    // rank_level = 4
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
            DB::raw("4 as rank_level") // Set 4 agar muncul paling bawah
        )
        ->where('desa.nama_desa', 'LIKE', "%{$search}%");

    // --- UNION & PENGURUTAN ---
    $results = $provinsi
        ->unionAll($kabupaten)
        ->unionAll($kecamatan)
        ->unionAll($desa)
        // LOGIC PENGURUTAN:
        // 1. Relevansi Teks: Jika diawali huruf pencarian (misal 'Krak' ketemu 'Krakatau'), taruh paling atas.
        // 2. Hirarki Wilayah: Provinsi (1) -> Kab (2) -> Kec (3) -> Desa (4).
        // 3. Alfabetis: Jika rank sama, urutkan A-Z.
        ->orderByRaw("CASE WHEN text LIKE '{$search}%' THEN 0 ELSE 1 END")
        ->orderBy('rank_level', 'asc') 
        ->orderBy('text', 'asc')
        ->limit(20)
        ->get();

    return response()->json($results);
}
}

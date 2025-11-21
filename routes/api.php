<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Pengaduan\PengaduanController;
use App\Http\Controllers\Api\Pengaduan\KategoriPengaduanController;
use App\Http\Controllers\Api\Anggota\UnitController;
use App\Http\Controllers\Api\Anggota\AnggotaController;
use App\Http\Controllers\Api\Anggota\JabatanController;
use App\Http\Controllers\Api\DokumenRegulasi\RegulasiController;
use App\Http\Controllers\Api\DokumenRegulasi\RegulationProgressController;
use App\Http\Controllers\Api\ManajemenLaporan\LaporanHarianController;
use App\Http\Controllers\Api\ManajemenLaporan\LampiranLaporanController;
use App\Http\Controllers\Api\Humas\GaleriController;
use App\Http\Controllers\Api\Humas\KontenController;
use App\Http\Controllers\Operasi\OperasiController;
use App\Http\Controllers\Operasi\DisposisiController;
use App\Http\Controllers\Operasi\OperasiPenugasanController;
use App\Http\Controllers\Penindakan\PenindakanController;


/**
 * ==========================
 *  PUBLIC ROUTES
 * ==========================
 */
Route::post('login', [AuthController::class, 'login']);

Route::post('pengaduan', [PengaduanController::class, 'store']);
Route::post('lacak-pengaduan', [PengaduanController::class, 'lacakNomorTiket']);

Route::get('konten-publik', [KontenController::class, 'KontenPublik']);
Route::get('konten-publik/{slug}', [KontenController::class, 'detailKonten']);


/**
 * ==========================
 * AUTH SANCTUM
 * ==========================
 */
Route::middleware('auth:sanctum')->group(function () {

    // user login
    Route::get('/user', fn(Request $request) => $request->user());

    Route::post('logout', [AuthController::class, 'logout']);


    /**
     * ==========================
     * PENGADUAN & DISPOSISI
     * role: super_admin, operator
     * ==========================
     */
    Route::middleware('role:super_admin,operator')->group(function () {
        Route::get('pengaduan', [PengaduanController::class, 'index']);
        Route::get('pengaduan/{id}', [PengaduanController::class, 'show']);
        Route::put('pengaduan/{id}', [PengaduanController::class, 'update']);
        Route::delete('pengaduan/{id}', [PengaduanController::class, 'destroy']);
        Route::get('pengaduan-tolak', [PengaduanController::class, 'setDitolak']);

        Route::get('disposisi', [DisposisiController::class, 'index']);
        Route::post('disposisi', [DisposisiController::class, 'store']);
        Route::get('disposisi/{id}', [DisposisiController::class, 'show']);
        Route::put('disposisi/{id}', [DisposisiController::class, 'update']);
        Route::delete('disposisi/{id}', [DisposisiController::class, 'destroy']);
    });


    /**
     * ==========================
     * OPERASI
     * role: super_admin, komandan_regu
     * ==========================
     */
    Route::middleware('role:super_admin,komandan_regu')->group(function () {
        Route::get('operasi', [OperasiController::class, 'index']);
        Route::post('operasi', [OperasiController::class, 'store']);
        Route::get('operasi/{id}', [OperasiController::class, 'show']);
        Route::put('operasi/{id}', [OperasiController::class, 'update']);
        Route::delete('operasi/{id}', [OperasiController::class, 'destroy']);
    });


    /**
     * ==========================
     * PENINDAKAN
     * ==========================
     */
    // semua role: melihat penindakan
    Route::middleware('role:super_admin,komandan_regu,anggota_regu,ppns')->group(function () {
        Route::get('penindakan', [PenindakanController::class, 'index']);
        Route::get('penindakan/{id}', [PenindakanController::class, 'show']);

        Route::get('disposisi-anggota', [DisposisiController::class, 'disposisiAnggota'])
            ->middleware('role:super_admin,komandan_regu,anggota_regu');

        Route::get('operasi-anggota', [OperasiController::class, 'getOperasiAnggota'])
            ->middleware('role:super_admin,anggota_regu');
    });

    // super_admin + anggota_regu: create/update/delete penindakan
    Route::middleware('role:super_admin,anggota_regu')
        ->prefix('penindakan')
        ->group(function () {
            Route::post('/', [PenindakanController::class, 'store']);
            Route::put('/{id}', [PenindakanController::class, 'update']);
            Route::delete('/{id}', [PenindakanController::class, 'destroy']);
        });

    // PPNS validasi
    Route::middleware('role:ppns')->post('penindakan/{id}/validasi-ppns', [PenindakanController::class, 'validasiPPNS']);
});


/**
 * ==========================
 * VALIDASI PENINDAKAN GLOBAL (super_admin & ppns)
 * ==========================
 */
Route::middleware('auth:sanctum', 'role:super_admin,ppns')
    ->put('penindakan-validasi-ppns/{id}', [PenindakanController::class, 'validasiPPNS']);


/**
 * ==========================
 * SUPER ADMIN MANAGEMENT
 * ==========================
 */
Route::middleware('auth:sanctum', 'role:super_admin')->group(function () {

    Route::post('register', [AuthController::class, 'register']);

    // jabatan
    Route::get('jabatan', [JabatanController::class, 'index']);
    Route::post('jabatan', [JabatanController::class, 'store']);
    Route::get('jabatan/{id}', [JabatanController::class, 'show']);
    Route::put('jabatan/{id}', [JabatanController::class, 'update']);
    Route::delete('jabatan/{id}', [JabatanController::class, 'destroy']);

    // unit
    Route::get('unit', [UnitController::class, 'index']);
    Route::post('unit', [UnitController::class, 'store']);
    Route::get('unit/{id}', [UnitController::class, 'show']);
    Route::put('unit/{id}', [UnitController::class, 'update']);
    Route::delete('unit/{id}', [UnitController::class, 'destroy']);

    // anggota
    Route::get('anggota', [AnggotaController::class, 'index']);
    Route::post('anggota', [AnggotaController::class, 'store']);
    Route::get('anggota/{id}', [AnggotaController::class, 'show']);
    Route::put('anggota/{id}', [AnggotaController::class, 'update']);
    Route::delete('anggota/{id}', [AnggotaController::class, 'destroy']);

    // kategori pengaduan
    Route::get('kategori-pengaduan', [KategoriPengaduanController::class, 'index']);
    Route::post('kategori-pengaduan', [KategoriPengaduanController::class, 'store']);
    Route::get('kategori-pengaduan/{id}', [KategoriPengaduanController::class, 'show']);
    Route::put('kategori-pengaduan/{id}', [KategoriPengaduanController::class, 'update']);
    Route::delete('kategori-pengaduan/{id}', [KategoriPengaduanController::class, 'destroy']);

    // regulasi
    Route::get('regulasi', [RegulasiController::class, 'index']);
    Route::post('regulasi', [RegulasiController::class, 'store']);
    Route::get('regulasi/{id}', [RegulasiController::class, 'show']);
    Route::put('regulasi/{id}', [RegulasiController::class, 'update']);
    Route::delete('regulasi/{id}', [RegulasiController::class, 'destroy']);
    Route::get('progress-anggota', [RegulasiController::class, 'GetallProgress']);

    // regulation progress
    Route::get('getprogres', [RegulationProgressController::class, 'getProgress']);
    Route::post('progres', [RegulationProgressController::class, 'Progress']);
    Route::post('sedang-membaca', [RegulationProgressController::class, 'ProgressMembaca']);
    Route::post('penanda', [RegulationProgressController::class, 'Penanda']);
    Route::get('penanda/{id}', [RegulationProgressController::class, 'GetPenanda']);
    Route::put('penanda/{id}', [RegulationProgressController::class, 'UpdatePenanda']);
    Route::delete('penanda/{id}', [RegulationProgressController::class, 'DestroyPenanda']);

    // laporan & humas
    Route::get('laporan-admin', [LaporanHarianController::class, 'getallLaporan']);
    Route::get('laporan', [LaporanHarianController::class, 'index']);
    Route::post('laporan', [LaporanHarianController::class, 'store']);
    Route::get('laporan/{id}', [LaporanHarianController::class, 'show']);
    Route::put('laporan/{id}', [LaporanHarianController::class, 'update']);
    Route::delete('laporan/{id}', [LaporanHarianController::class, 'destroy']);

    Route::get('galeri', [GaleriController::class, 'index']);
    Route::post('galeri', [GaleriController::class, 'store']);
    Route::get('galeri/{id}', [GaleriController::class, 'show']);
    Route::put('galeri/{id}', [GaleriController::class, 'update']);
    Route::delete('galeri/{id}', [GaleriController::class, 'destroy']);

    Route::get('konten', [KontenController::class, 'index']);
    Route::post('konten', [KontenController::class, 'store']);
    Route::get('konten/{slug}', [KontenController::class, 'show']);
    Route::put('konten/{slug}', [KontenController::class, 'update']);
    Route::delete('konten/{slug}', [KontenController::class, 'destroy']);
});


/**
 * ==========================
 * ANGGOTA REGU
 * ==========================
 */
Route::middleware('auth:sanctum', 'role:anggota_regu')->group(function () {
    Route::get('lampiran', [LampiranLaporanController::class, 'index']);
    Route::post('lampiran', [LampiranLaporanController::class, 'store']);
    Route::get('lampiran/{id}', [LampiranLaporanController::class, 'show']);
    Route::put('lampiran/{id}', [LampiranLaporanController::class, 'update']);
});


/**
 * ==========================
 * KOMANDAN REGU
 * ==========================
 */
Route::middleware('auth:sanctum', 'role:komandan_regu')->group(function () {
    Route::get('laporan-komandan', [LampiranLaporanController::class, 'indexKomandan']);
    Route::put('laporan-komandan/{id}', [LampiranLaporanController::class, 'AccbyKomandan']);
});


// use Illuminate\Http\Request;
// use App\Models\Operasi\Disposisi;
// use Illuminate\Support\Facades\Route;
// use App\Http\Controllers\Api\Auth\AuthController;
// use App\Http\Controllers\Operasi\OperasiController;
// use App\Http\Controllers\Api\Anggota\UnitController;
// use App\Http\Controllers\Operasi\DisposisiController;
// use App\Http\Controllers\Api\Anggota\AnggotaController;
// use App\Http\Controllers\Api\Anggota\JabatanController;
// use App\Http\Controllers\Api\DokumenRegulasi\RegulasiController;
// use App\Http\Controllers\Api\DokumenRegulasi\RegulationProgressController;
// use App\Http\Controllers\Api\Humas\GaleriController;
// use App\Http\Controllers\Api\Humas\KontenController;
// use App\Http\Controllers\Api\ManajemenLaporan\LampiranLaporanController;
// use App\Http\Controllers\Api\ManajemenLaporan\LaporanHarianController;
// use App\Http\Controllers\Api\Pengaduan\PengaduanController;
// use App\Http\Controllers\Operasi\OperasiPenugasanController;
// use App\Http\Controllers\Api\Pengaduan\KategoriPengaduanController;
// use App\Http\Controllers\Penindakan\PenindakanController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

// Route::post('login', [AuthController::class, 'login']);


// Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');


// Route::post('pengaduan', [PengaduanController::class, 'store']);
// Route::post('lacak-pengaduan', [PengaduanController::class, 'lacakNomorTiket']);

// Route::middleware('auth:sanctum', 'role:super_admin,operator')->group(function () {
//     Route::get('pengaduan', [PengaduanController::class, 'index']);
//     Route::get('pengaduan/{id}', [PengaduanController::class, 'show']);
//     Route::put('pengaduan/{id}', [PengaduanController::class, 'update']);
//     Route::delete('pengaduan/{id}', [PengaduanController::class, 'destroy']);
//     Route::get('pengaduan-tolak', [PengaduanController::class, 'setDitolak']);

//     Route::get('disposisi', [DisposisiController::class, 'index']);
//     Route::post('disposisi', [DisposisiController::class, 'store']);
//     Route::get('disposisi/{id}', [DisposisiController::class, 'show']);
//     Route::put('disposisi/{id}', [DisposisiController::class, 'update']);
//     Route::delete('disposisi/{id}', [DisposisiController::class, 'destroy']);
// });

// Route::middleware('auth:sanctum', 'role:super_admin,komandan_regu')->group(function () {
//     Route::get('operasi', [OperasiController::class, 'index']);
//     Route::post('operasi', [OperasiController::class, 'store']);
//     Route::get('operasi/{id}', [OperasiController::class, 'show']);
//     Route::put('operasi/{id}', [OperasiController::class, 'update']);
//     Route::delete('operasi/{id}', [OperasiController::class, 'destroy']);

//     // Route::get('operasi-penugasan', [OperasiPenugasanController::class, 'index']);
//     // Route::post('operasi-penugasan', [OperasiPenugasanController::class, 'store']);
//     // Route::get('operasi-penugasan/{id}', [OperasiPenugasanController::class, 'show']);
//     // Route::put('operasi-penugasan/{id}', [OperasiPenugasanController::class, 'update']);
//     // Route::delete('operasi-penugasan/{id}', [OperasiPenugasanController::class, 'destroy']);
// });

// Route::middleware(['auth:sanctum'])->group(function () {

//     // semua role bisa melihat penindakan & detail
//     Route::middleware(['role:super_admin,komandan_regu,anggota_regu,ppns'])->group(function () {
//         Route::get('penindakan', [PenindakanController::class, 'index']);
//         Route::get('penindakan/{id}', [PenindakanController::class, 'show']);

//         Route::get('disposisi-anggota', [DisposisiController::class, 'disposisiAnggota'])
//             ->middleware('role:super_admin,komandan_regu,anggota_regu');

//         Route::get('operasi-anggota', [OperasiController::class, 'getOperasiAnggota'])
//             ->middleware('role:super_admin,anggota_regu');
//     });

//     // hanya super_admin & anggota_regu boleh create/update/delete
//     Route::middleware(['role:super_admin,anggota_regu'])->prefix('penindakan')->group(function () {
//         Route::post('/', [PenindakanController::class, 'store']);
//         Route::put('/{id}', [PenindakanController::class, 'update']);
//         Route::delete('/{id}', [PenindakanController::class, 'destroy']);
//     });

//     // khusus PPNS untuk validasi
//     Route::middleware(['role:ppns'])->group(function () {
//         Route::post('penindakan/{id}/validasi-ppns', [PenindakanController::class, 'validasiPPNS']);
//     });
// });

// Route::middleware('auth:sanctum', 'role:super_admin,ppns')->group(function () {
//     Route::put('penindakan-validasi-ppns/{id}', [PenindakanController::class, 'validasiPPNS']);
// });

// Route::middleware('auth:sanctum', 'role:super_admin')->group(function () {
//     Route::post('register', [AuthController::class, 'register']);

//     Route::get('jabatan', [JabatanController::class, 'index']);
//     Route::post('jabatan', [JabatanController::class, 'store']);
//     Route::get('jabatan/{id}', [JabatanController::class, 'show']);
//     Route::put('jabatan/{id}', [JabatanController::class, 'update']);
//     Route::delete('jabatan/{id}', [JabatanController::class, 'destroy']);

//     Route::get('unit', [UnitController::class, 'index']);
//     Route::post('unit', [UnitController::class, 'store']);
//     Route::get('unit/{id}', [UnitController::class, 'show']);
//     Route::put('unit/{id}', [UnitController::class, 'update']);
//     Route::delete('unit/{id}', [UnitController::class, 'destroy']);

//     Route::get('anggota', [AnggotaController::class, 'index']);
//     Route::post('anggota', [AnggotaController::class, 'store']);
//     Route::get('anggota/{id}', [AnggotaController::class, 'show']);
//     Route::put('anggota/{id}', [AnggotaController::class, 'update']);
//     Route::delete('anggota/{id}', [AnggotaController::class, 'destroy']);

//     Route::get('kategori-pengaduan', [KategoriPengaduanController::class, 'index']);
//     Route::post('kategori-pengaduan', [KategoriPengaduanController::class, 'store']);
//     Route::get('kategori-pengaduan/{id}', [KategoriPengaduanController::class, 'show']);
//     Route::put('kategori-pengaduan/{id}', [KategoriPengaduanController::class, 'update']);
//     Route::delete('kategori-pengaduan/{id}', [KategoriPengaduanController::class, 'destroy']);

//     Route::get('regulasi', [RegulasiController::class, 'index']);
//     Route::post('regulasi', [RegulasiController::class, 'store']);
//     Route::get('regulasi/{id}', [RegulasiController::class, 'show']);
//     Route::put('regulasi/{id}', [RegulasiController::class, 'update']);
//     Route::delete('regulasi/{id}', [RegulasiController::class, 'destroy']);
//     Route::get('progress-anggota', [RegulasiController::class, 'GetallProgress']);

//     Route::get('getprogres', [RegulationProgressController::class, 'getProgress']);
//     Route::post('progres', [RegulationProgressController::class, 'Progress']);
//     Route::post('sedang-membaca', [RegulationProgressController::class, 'ProgressMembaca']);
//     Route::post('penanda', [RegulationProgressController::class, 'Penanda']);
//     Route::get('penanda/{id}', [RegulationProgressController::class, 'GetPenanda']);
//     Route::put('penanda/{id}', [RegulationProgressController::class, 'UpdatePenanda']);
//     Route::delete('penanda/{id}', [RegulationProgressController::class, 'DestroyPenanda']);

//     // laporan dashboard admin
//     Route::get('laporan-admin', [LaporanHarianController::class, 'getallLaporan']);
//     Route::get('laporan-admin', [LaporanHarianController::class, 'getallLaporan']);
//     Route::get('laporan', [LaporanHarianController::class, 'index']);
//     Route::post('laporan', [LaporanHarianController::class, 'store']);
//     Route::get('laporan/{id}', [LaporanHarianController::class, 'show']);
//     Route::put('laporan/{id}', [LaporanHarianController::class, 'update']);
//     Route::delete('laporan/{id}', [LaporanHarianController::class, 'destroy']);

//     Route::get('galeri', [GaleriController::class, 'index']);
//     Route::post('galeri', [GaleriController::class, 'store']);
//     Route::get('galeri/{id}', [GaleriController::class, 'show']);
//     Route::put('galeri/{id}', [GaleriController::class, 'update']);
//     Route::delete('galeri/{id}', [GaleriController::class, 'destroy']);

//     Route::get('konten', [KontenController::class, 'index']);
//     Route::post('konten', [KontenController::class, 'store']);
//     Route::get('konten/{slug}', [KontenController::class, 'show']);
//     Route::put('konten/{slug}', [KontenController::class, 'update']);
//     Route::delete('konten/{slug}', [KontenController::class, 'destroy']);
// });

// Route::middleware('auth:sanctum', 'role:anggota_regu')->group(function () {
//     Route::get('lampiran', [LampiranLaporanController::class, 'index']);
//     Route::post('lampiran', [LampiranLaporanController::class, 'store']);
//     Route::get('lampiran/{id}', [LampiranLaporanController::class, 'show']);
//     Route::put('lampiran/{id}', [LampiranLaporanController::class, 'update']);
// });
// Route::middleware('auth:sanctum', 'role:komandan_regu')->group(function () {
//     Route::get('laporan-komandan', [LampiranLaporanController::class, 'indexKomandan']);
//     Route::put('laporan-komandan/{id}', [LampiranLaporanController::class, 'AccbyKomandan']);
// });

// // masyarakat
// Route::get('konten-publik', [KontenController::class, 'KontenPublik']);
// Route::get('konten-publik/{slug}', [KontenController::class, 'detailKonten']);

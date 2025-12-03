<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LokasiController;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\PPID\PPIDController;
use App\Http\Controllers\Api\Anggota\UnitController;
use App\Http\Controllers\Api\Humas\GaleriController;
use App\Http\Controllers\Api\Humas\KontenController;
use App\Http\Controllers\Api\Anggota\AnggotaController;
use App\Http\Controllers\Api\Anggota\AnggotaImportController;
use App\Http\Controllers\Api\Anggota\JabatanController;
use App\Http\Controllers\Api\DokumenRegulasi\KategoriRegulasiController;
use App\Http\Controllers\Api\Operasi\OperasiController;
use App\Http\Controllers\Api\Operasi\DisposisiController;
use App\Http\Controllers\Api\Pengaduan\PengaduanController;
use App\Http\Controllers\Api\Penindakan\PenindakanController;
use App\Http\Controllers\Api\DokumenRegulasi\RegulasiController;
use App\Http\Controllers\Api\Pengaduan\KategoriPengaduanController;
use App\Http\Controllers\Api\ManajemenLaporan\LaporanHarianController;
use App\Http\Controllers\Api\ManajemenLaporan\LampiranLaporanController;
use App\Http\Controllers\Api\DokumenRegulasi\RegulationProgressController;

/**
 * ==========================
 *  PUBLIC ROUTES
 * ==========================
 */
Route::post('login', [AuthController::class, 'login']);

Route::post('pengaduan', [PengaduanController::class, 'store']);
Route::post('lacak-pengaduan', [PengaduanController::class, 'lacakNomorTiket']);

Route::get('berita-publik', [KontenController::class, 'KontenPublik']);
Route::get('konten-publik/{slug}', [KontenController::class, 'detailKonten']);

Route::get('konten-galeri', [GaleriController::class, 'galeripublic']);

Route::post('permohonan-ppid', [PPIDController::class, 'permohonanPPID']);
Route::post('lacak-permohonan-ppid', [PPIDController::class, 'lacakPPID']);

Route::get('kategori-pengaduan', [KategoriPengaduanController::class, 'index']);

/**
 * ==========================
 * AUTH SANCTUM
 * ==========================
 */
Route::get('search-lokasi', [LokasiController::class, 'search']);

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/user', fn(Request $request) => $request->user());
    Route::post('logout', [AuthController::class, 'logout']);



    /**
     * ==========================
     * PENGADUAN & DISPOSISI
     * role: super_admin | operator
     * ==========================
     */
    Route::middleware('role:super_admin|operator')->group(function () {
        Route::get('pengaduan', [PengaduanController::class, 'index']);
        Route::get('pengaduan/{id}', [PengaduanController::class, 'show']);
        Route::put('pengaduan/{id}', [PengaduanController::class, 'update']);
        Route::delete('pengaduan/{id}', [PengaduanController::class, 'destroy']);
        Route::post('pengaduan-tolak/{id}', [PengaduanController::class, 'setDitolak']);

        Route::get('list-komandan', [UserController::class, 'getAllKomandan']);

        Route::get('disposisi', [DisposisiController::class, 'index']);
        Route::post('disposisi', [DisposisiController::class, 'store']);
        Route::get('disposisi/{id}', [DisposisiController::class, 'show']);
        Route::put('disposisi/{id}', [DisposisiController::class, 'update']);
        Route::delete('disposisi/{id}', [DisposisiController::class, 'destroy']);
    });

    /**
     * ==========================
     * OPERASI
     * role: super_admin | komandan_regu
     * ==========================
     */
    Route::middleware('role:super_admin|komandan_regu')->group(function () {
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
    Route::middleware('role:super_admin|komandan_regu|ppns')->group(function () {
        Route::get('penindakan', [PenindakanController::class, 'index']);
        Route::get('penindakan/{id}', [PenindakanController::class, 'show']);

        Route::get('disposisi-komandan', [DisposisiController::class, 'getDisposisiKomandan'])
            ->middleware('role:super_admin|komandan_regu');

        Route::get('operasi-anggota', [OperasiController::class, 'getOperasiAnggota'])
            ->middleware('role:super_admin|anggota_regu');
    });

    // CRUD penindakan
    Route::middleware('role:super_admin|komandan_regu')
        ->prefix('penindakan')
        ->group(function () {
            Route::post('/', [PenindakanController::class, 'store']);
            Route::put('/{id}', [PenindakanController::class, 'update']);
            Route::delete('/{id}', [PenindakanController::class, 'destroy']);
        });

    // Validasi PPNS
});


/**
 * ==========================
 * VALIDASI PENINDAKAN GLOBAL
 * role: super_admin | ppns
 * ==========================
 */
Route::middleware('auth:sanctum', 'role:super_admin|ppns')
    ->post('penindakan-validasi-ppns/{id}', [PenindakanController::class, 'validasiPPNS']);


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

    // anggotaJ
    Route::get('anggota', [AnggotaController::class, 'index']);
    Route::post('anggota', [AnggotaController::class, 'store']);
    Route::get('anggota/{id}', [AnggotaController::class, 'show']);
    Route::put('anggota/{id}', [AnggotaController::class, 'update']);
    Route::delete('anggota/{id}', [AnggotaController::class, 'destroy']);

    // kategori pengaduan

    Route::post('kategori-pengaduan', [KategoriPengaduanController::class, 'store']);
    Route::get('kategori-pengaduan/{id}', [KategoriPengaduanController::class, 'show']);
    Route::put('kategori-pengaduan/{id}', [KategoriPengaduanController::class, 'update']);
    Route::delete('kategori-pengaduan/{id}', [KategoriPengaduanController::class, 'destroy']);

    // laporan
    Route::get('laporan', [LaporanHarianController::class, 'getAll']);
    Route::post('laporan', [LaporanHarianController::class, 'store']);
    Route::get('laporan/{id}', [LaporanHarianController::class, 'show']);
    Route::put('laporan/{id}', [LaporanHarianController::class, 'update']);
    Route::delete('laporan/{id}', [LaporanHarianController::class, 'destroy']);

    // Kategori regulasi
    Route::get('kategori-regulasi', [KategoriRegulasiController::class, 'index']);
    Route::post('kategori-regulasi', [KategoriRegulasiController::class, 'store']);
    Route::get('kategori-regulasi/{id}', [KategoriRegulasiController::class, 'show']);
    Route::put('kategori-regulasi/{id}', [KategoriRegulasiController::class, 'update']);
    Route::delete('kategori-regulasi/{id}', [KategoriRegulasiController::class, 'destroy']);

    // regulasi
    Route::get('regulasi', [RegulasiController::class, 'index']);
    Route::post('regulasi', [RegulasiController::class, 'store']);
    Route::get('regulasi/{id}', [RegulasiController::class, 'show']);
    Route::put('regulasi/{id}', [RegulasiController::class, 'update']);
    Route::delete('regulasi/{id}', [RegulasiController::class, 'destroy']);
    // Route::get('progress-anggota', [RegulasiController::class, 'GetallProgress']);

    // regulation progress
    // Route::get('getprogres', [RegulationProgressController::class, 'getProgress']);
    // Route::post('progres', [RegulationProgressController::class, 'Progress']);
    // Route::post('sedang-membaca', [RegulationProgressController::class, 'ProgressMembaca']);
    // Route::post('penanda', [RegulationProgressController::class, 'Penanda']);
    // Route::get('penanda/{id}', [RegulationProgressController::class, 'GetPenanda']);
    // Route::put('penanda/{id}', [RegulationProgressController::class, 'UpdatePenanda']);
    // Route::delete('penanda/{id}', [RegulationProgressController::class, 'DestroyPenanda']);

    // laporan
    // Route::get('laporan-admin', [LaporanHarianController::class, 'getallLaporan']);
    // Route::get('laporan', [LaporanHarianController::class, 'index']);
    // Route::post('laporan', [LaporanHarianController::class, 'store']);
    // Route::get('laporan/{id}', [LaporanHarianController::class, 'show']);
    // Route::put('laporan/{id}', [LaporanHarianController::class, 'update']);
    // Route::delete('laporan/{id}', [LaporanHarianController::class, 'destroy']);

    Route::get('ppid', [PPIDController::class, 'index']);
    Route::post('validasi-ppid/{id}', [PPIDController::class, 'validasiPPID']);

    Route::post('anggota-import', [AnggotaImportController::class, 'import']);
});

Route::middleware(['auth:sanctum', 'role:humas|super_admin'])->group(function () {
    Route::get('galeri', [GaleriController::class, 'index']);
    Route::post('galeri', [GaleriController::class, 'store']);
    Route::get('galeri/{id}', [GaleriController::class, 'show']);
    Route::put('galeri/{id}', [GaleriController::class, 'update']);
    Route::delete('galeri/{id}', [GaleriController::class, 'destroy']);

    Route::get('berita', [KontenController::class, 'indexBerita']);
    Route::post('berita', [KontenController::class, 'store']);
    Route::get('berita/{id}', [KontenController::class, 'show']);
    Route::put('berita/{id}', [KontenController::class, 'update']);
    Route::delete('berita/{id}', [KontenController::class, 'destroy']);

    Route::get('agenda', [KontenController::class, 'indexAgenda']);
    Route::post('agenda', [KontenController::class, 'storeAgenda']);
    Route::get('agenda/{id}', [KontenController::class, 'showAgenda']);
    Route::put('agenda/{id}', [KontenController::class, 'updateAgenda']);
    Route::delete('agenda/{id}', [KontenController::class, 'destroy']);

    Route::get('Himbauan', [KontenController::class, 'indexHimbauan']);
    Route::post('Himbauan', [KontenController::class, 'storeHimbauan']);
    Route::get('Himbauan/{id}', [KontenController::class, 'showHimbauan']);
    Route::put('Himbauan/{id}', [KontenController::class, 'updateHimbauan']);
    Route::delete('Himbauan/{id}', [KontenController::class, 'destroy']);
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

    // regulation progress
    Route::get('progress', [RegulationProgressController::class, 'listregulasi']);
    Route::get('progress/{id}', [RegulationProgressController::class, 'detailregulasi']);
    Route::post('progres', [RegulationProgressController::class, 'catatprogresbacaan']);
    Route::get('penanda', [RegulationProgressController::class, 'bookmartregulasi']);
    Route::get('penanda/{id}', [RegulationProgressController::class, 'detailbookmark']);
    Route::post('penanda-pasal', [RegulationProgressController::class, 'tandaiPasal']);
    // Route::put('penanda-pasal/{id}', [RegulationProgressController::class, 'updatetandaiPasal']);
    Route::post('penanda-halaman', [RegulationProgressController::class, 'tandaiHalaman']);
    // Route::put('penanda-halaman/{id}', [RegulationProgressController::class, 'updatetandaihalaman']);
    Route::delete('penanda/{id}', [RegulationProgressController::class, 'DestroyPenanda']);
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

Route::middleware(['auth:sanctum', 'role:komandan_regu|super_admin'])->group(function () {
    Route::get('/monitoring-literasi', [RegulationProgressController::class, 'monitoringLiterasi']);
});

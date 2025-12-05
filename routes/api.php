<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LokasiController;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\PPID\PPIDController;
use App\Http\Controllers\Api\Anggota\UnitController;
use App\Http\Controllers\Api\Humas\AgendaController;
use App\Http\Controllers\Api\Humas\BeritaController;
use App\Http\Controllers\Api\Humas\GaleriController;
use App\Http\Controllers\Api\Humas\HimbauanController;
use App\Http\Controllers\Api\Anggota\AnggotaController;
use App\Http\Controllers\Api\Anggota\JabatanController;
use App\Http\Controllers\Api\Operasi\OperasiController;
use App\Http\Controllers\Api\Operasi\DisposisiController;
use App\Http\Controllers\Api\Dashboard\DashboardController;
use App\Http\Controllers\Api\Pengaduan\PengaduanController;
use App\Http\Controllers\Api\Anggota\AnggotaImportController;
use App\Http\Controllers\Api\Humas\StatistikPublikController;
use App\Http\Controllers\Api\Penindakan\PenindakanController;
use App\Http\Controllers\Api\DokumenRegulasi\RegulasiController;
use App\Http\Controllers\Api\Pengaduan\KategoriPengaduanController;
use App\Http\Controllers\Api\ManajemenLaporan\LaporanHarianController;
use App\Http\Controllers\Api\DokumenRegulasi\KategoriRegulasiController;
use App\Http\Controllers\Api\ManajemenLaporan\LampiranLaporanController;
use App\Http\Controllers\Api\DokumenRegulasi\RegulationProgressController;
use App\Http\Controllers\Api\Operasi\PenugasanController;

/**
 * ==========================
 * PUBLIC ROUTES
 * Throttle: 60 requests per minute
 * ==========================
 */
Route::middleware('throttle:120,1')->group(function () {
    // Authentication
    Route::post('login', [AuthController::class, 'login'])->name('login');
    Route::post('forgot', [AuthController::class, 'forgotPassword']);
    Route::post('reset', [AuthController::class, 'resetPassword'])->name('password.reset');

    // Pengaduan Public
    Route::prefix('pengaduan')->name('pengaduan.')->group(function () {
        Route::post('/', [PengaduanController::class, 'store'])->name('store');
        Route::post('lacak', [PengaduanController::class, 'lacakNomorTiket'])->name('lacak');
    });

    // Statistik Public
    Route::prefix('statistik')->name('statistik.')->group(function () {
        Route::get('operasi-kategori', [StatistikPublikController::class, 'index'])->name('operasi-kategori');
        Route::get('bulanan', [StatistikPublikController::class, 'indexMonth'])->name('bulanan');
    });

    // Berita Public
    Route::prefix('berita-publik')->name('berita-publik.')->group(function () {
        Route::get('/', [BeritaController::class, 'beritaPublik'])->name('index');
        Route::get('{slug}', [BeritaController::class, 'detailKonten'])->name('show');
    });

    // Regulasi Public
    Route::prefix('regulasi-publik')->name('regulasi-publik.')->group(function () {
        Route::get('/', [RegulasiController::class, 'regulasiPublik'])->name('index');
        Route::get('filter', [RegulasiController::class, 'kategoriregulasi'])->name('filter');
    });

    // Himbauan Public
    Route::prefix('himbauan-publik')->name('himbauan-publik.')->group(function () {
        Route::get('/', [HimbauanController::class, 'himbauanPublik'])->name('index');
        Route::get('{slug}', [HimbauanController::class, 'detailKonten'])->name('show');
    });

    // Agenda Public
    Route::get('agenda-publik', [AgendaController::class, 'agendaPublik'])->name('agenda-publik.index');

    // Galeri Public
    Route::get('konten-galeri', [GaleriController::class, 'galeripublic'])->name('galeri-publik.index');

    // PPID Public
    Route::prefix('ppid')->name('ppid.')->group(function () {
        Route::post('permohonan', [PPIDController::class, 'permohonanPPID'])->name('permohonan');
        Route::post('lacak', [PPIDController::class, 'lacakPPID'])->name('lacak');
    });

    // Kategori Pengaduan Public
    Route::get('kategori-pengaduan', [KategoriPengaduanController::class, 'index'])->name('kategori-pengaduan.index');

    // PDF Viewer
    Route::get('pdf-viewer', [RegulasiController::class, 'showPdf'])->name('pdf-viewer');

    // Lokasi Search
    Route::get('search-lokasi', [LokasiController::class, 'search'])->name('lokasi.search');
});

/**
 * ==========================
 * AUTHENTICATED ROUTES
 * Throttle: 120 requests per minute
 * ==========================
 */
Route::middleware(['auth:sanctum', 'throttle:200,1'])->group(function () {

    // Auth User Info
    // Route::get('user', fn(Request $request) => $request->user())->name('user.info');
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('password', [AuthController::class, 'changePassword']);

    /**
     * ==========================
     * PENGADUAN & DISPOSISI
     * Role: super_admin | operator
     * ==========================
     */
    Route::middleware('role:super_admin|operator')->group(function () {
        // Pengaduan Management
        Route::prefix('pengaduan')->name('pengaduan.')->group(function () {
            Route::get('/', [PengaduanController::class, 'index'])->name('index');
            Route::get('{id}', [PengaduanController::class, 'show'])->name('show');
            Route::put('{id}', [PengaduanController::class, 'update'])->name('update');
            Route::delete('{id}', [PengaduanController::class, 'destroy'])->name('destroy');
            Route::post('tolak/{id}', [PengaduanController::class, 'setDitolak'])->name('tolak');
        });

        // User - List Komandan
        Route::get('list-komandan', [UserController::class, 'getAllKomandan'])->name('user.komandan');

        // Disposisi Management
        Route::prefix('disposisi')->name('disposisi.')->group(function () {
            Route::get('/', [DisposisiController::class, 'index'])->name('index');
            Route::get('{id}', [DisposisiController::class, 'show'])->name('show');
            Route::post('/', [DisposisiController::class, 'store'])->name('store');
            Route::put('{id}', [DisposisiController::class, 'update'])->name('update');
            Route::delete('{id}', [DisposisiController::class, 'destroy'])->name('destroy');
        });
    });

    Route::prefix('disposisi')->middleware('role:super_admin|komandan_regu|operator')->name('disposisi.')->group(function () {
        Route::get('/', [DisposisiController::class, 'index'])->name('index');
        Route::get('{id}', [DisposisiController::class, 'show'])->name('show');
    });
    /**
     * ==========================
     * OPERASI
     * Role: super_admin | komandan_regu
     * ==========================
     */
    Route::middleware('role:super_admin|komandan_regu')->group(function () {
        Route::post('/penugasan', [PenugasanController::class, 'store'])->name('penugasan.store');
        Route::put('/penugasan/{id}', [PenugasanController::class, 'update'])->name('penugasan.update');

        Route::prefix('operasi')->name('operasi.')->group(function () {
            Route::get('/', [OperasiController::class, 'index'])->name('index');
            Route::post('/', [OperasiController::class, 'store'])->name('store');
            Route::get('{id}', [OperasiController::class, 'show'])->name('show');
            Route::put('{id}', [OperasiController::class, 'update'])->name('update');
            Route::delete('{id}', [OperasiController::class, 'destroy'])->name('destroy');
        });
    });

    Route::prefix('operasi')->middleware('role:super_admin|komandan_regu|anggota_regu')->name('operasi.')->group(function () {
        Route::get('/', [OperasiController::class, 'index'])->name('index');
        Route::get('{id}', [OperasiController::class, 'show'])->name('show');
    });

    /**
     * ==========================
     * PENINDAKAN
     * Role: super_admin | komandan_regu | ppns
     * ==========================
     */
    Route::middleware('role:super_admin|komandan_regu|ppns|anggota_regu')->group(function () {
        // Penindakan Read
        Route::prefix('penindakan')->name('penindakan.')->group(function () {
            Route::get('/', [PenindakanController::class, 'index'])->name('index');
            Route::get('{id}', [PenindakanController::class, 'show'])->name('show');

            // Validasi PPNS
            Route::middleware('role:super_admin|ppns')
                ->post('validasi-ppns/{id}', [PenindakanController::class, 'validasiPPNS'])
                ->name('validasi-ppns');
        });

        // Disposisi Komandan
        // Route::middleware('role:super_admin|komandan_regu')
        //     ->get('disposisi-komandan', [DisposisiController::class, 'getDisposisiKomandan'])
        //     ->name('disposisi.komandan');

        // Operasi Anggota
        Route::middleware('role:super_admin|anggota_regu')
            ->get('operasi-anggota', [OperasiController::class, 'getOperasiAnggota'])
            ->name('operasi.anggota');
    });

    // Penindakan CRUD (super_admin | komandan_regu only)
    Route::middleware('role:super_admin|anggota_regu')->prefix('penindakan')->name('penindakan.')->group(function () {

        Route::post('/validasi-komandan/{id}', [PenindakanController::class, 'validasiKomandan'])->name('validasiKomandan');
        Route::post('/', [PenindakanController::class, 'store'])->name('store');
        Route::put('{id}', [PenindakanController::class, 'update'])->name('update');
        Route::delete('{id}', [PenindakanController::class, 'destroy'])->name('destroy');
    });

    Route::middleware('role:super_admin|anggota_regu|komandan_regu')->group(function () {
        // Laporan Harian Resource
        Route::prefix('laporan')->name('laporan.')->group(function () {
            Route::get('/', [LaporanHarianController::class, 'getAll'])->name('index');
            Route::post('/', [LaporanHarianController::class, 'store'])->name('store');
            Route::get('{id}', [LaporanHarianController::class, 'show'])->name('show');
            Route::put('{id}', [LaporanHarianController::class, 'update'])->name('update');
            Route::delete('{id}', [LaporanHarianController::class, 'destroy'])->name('destroy');
        });
    });
    /**
     * ==========================
     * SUPER ADMIN MANAGEMENT
     * Role: super_admin
     * ==========================
     */
    Route::middleware('role:super_admin')->group(function () {

        //Dashboard
        Route::get('/stats', [DashboardController::class, 'index']);

        // User Registration
        Route::get('users', [AuthController::class, 'index'])->name('index.users');
        Route::get('users/{id}', [AuthController::class, 'show'])->name('show.users');
        Route::post('register', [AuthController::class, 'register'])->name('register.user');
        Route::put('users/{id}', [AuthController::class, 'update'])->name('update.user');

        // Jabatan Resource
        Route::prefix('jabatan')->name('jabatan.')->group(function () {
            Route::get('/', [JabatanController::class, 'index'])->name('index');
            Route::post('/', [JabatanController::class, 'store'])->name('store');
            Route::get('{id}', [JabatanController::class, 'show'])->name('show');
            Route::put('{id}', [JabatanController::class, 'update'])->name('update');
            Route::delete('{id}', [JabatanController::class, 'destroy'])->name('destroy');
        });

        // Unit Resource
        Route::prefix('unit')->name('unit.')->group(function () {
            Route::get('/', [UnitController::class, 'index'])->name('index');
            Route::post('/', [UnitController::class, 'store'])->name('store');
            Route::get('{id}', [UnitController::class, 'show'])->name('show');
            Route::put('{id}', [UnitController::class, 'update'])->name('update');
            Route::delete('{id}', [UnitController::class, 'destroy'])->name('destroy');
        });

        // Anggota Resource
        Route::prefix('anggota')->name('anggota.')->group(function () {
            Route::get('/', [AnggotaController::class, 'index'])->name('index');
            Route::get('{id}', [AnggotaController::class, 'show'])->name('show');
            Route::post('/', [AnggotaController::class, 'store'])->name('store');
            Route::put('{id}', [AnggotaController::class, 'update'])->name('update');
            Route::delete('{id}', [AnggotaController::class, 'destroy'])->name('destroy');
            Route::post('import', [AnggotaImportController::class, 'import'])->name('import');
        });

        // Kategori Pengaduan Resource
        Route::prefix('kategori-pengaduan')->name('kategori-pengaduan.')->group(function () {
            Route::post('/', [KategoriPengaduanController::class, 'store'])->name('store');
            Route::get('{id}', [KategoriPengaduanController::class, 'show'])->name('show');
            Route::put('{id}', [KategoriPengaduanController::class, 'update'])->name('update');
            Route::delete('{id}', [KategoriPengaduanController::class, 'destroy'])->name('destroy');
        });


        // Kategori Regulasi Resource
        Route::prefix('kategori-regulasi')->name('kategori-regulasi.')->group(function () {
            Route::get('/', [KategoriRegulasiController::class, 'index'])->name('index');
            Route::post('/', [KategoriRegulasiController::class, 'store'])->name('store');
            Route::get('{id}', [KategoriRegulasiController::class, 'show'])->name('show');
            Route::put('{id}', [KategoriRegulasiController::class, 'update'])->name('update');
            Route::delete('{id}', [KategoriRegulasiController::class, 'destroy'])->name('destroy');
        });

        // Regulasi Resource
        Route::prefix('regulasi')->name('regulasi.')->group(function () {
            Route::get('/', [RegulasiController::class, 'index'])->name('index');
            Route::post('/', [RegulasiController::class, 'store'])->name('store');
            Route::get('{id}', [RegulasiController::class, 'show'])->name('show');
            Route::put('{id}', [RegulasiController::class, 'update'])->name('update');
            Route::delete('{id}', [RegulasiController::class, 'destroy'])->name('destroy');
        });

        // PPID Management
        Route::prefix('ppid')->name('ppid.')->group(function () {
            Route::get('/', [PPIDController::class, 'index'])->name('index');
            Route::post('validasi/{id}', [PPIDController::class, 'validasiPPID'])->name('validasi');
        });
    });

    Route::prefix('anggota')->middleware('role:super_admin|komandan_regu')->name('anggota.')->group(function () {
        Route::get('/', [AnggotaController::class, 'index'])->name('index');
        Route::get('{id}', [AnggotaController::class, 'show'])->name('show');
    });

    /**
     * ==========================
     * HUMAS MANAGEMENT
     * Role: humas | super_admin
     * ==========================
     */
    Route::middleware('role:humas|super_admin')->group(function () {
        // Galeri Resource
        Route::prefix('galeri')->name('galeri.')->group(function () {
            Route::get('/', [GaleriController::class, 'index'])->name('index');
            Route::post('/', [GaleriController::class, 'store'])->name('store');
            Route::get('{id}', [GaleriController::class, 'show'])->name('show');
            Route::put('{id}', [GaleriController::class, 'update'])->name('update');
            Route::delete('{id}', [GaleriController::class, 'destroy'])->name('destroy');
        });

        // Berita Resource
        Route::prefix('berita')->name('berita.')->group(function () {
            Route::get('/', [BeritaController::class, 'indexBerita'])->name('index');
            Route::post('/', [BeritaController::class, 'store'])->name('store');
            Route::get('{id}', [BeritaController::class, 'show'])->name('show');
            Route::put('{id}', [BeritaController::class, 'update'])->name('update');
            Route::delete('{id}', [BeritaController::class, 'destroy'])->name('destroy');
        });

        // Agenda Resource
        Route::prefix('agenda')->name('agenda.')->group(function () {
            Route::get('/', [AgendaController::class, 'indexAgenda'])->name('index');
            Route::post('/', [AgendaController::class, 'storeAgenda'])->name('store');
            Route::get('{id}', [AgendaController::class, 'showAgenda'])->name('show');
            Route::put('{id}', [AgendaController::class, 'updateAgenda'])->name('update');
            Route::delete('{id}', [AgendaController::class, 'destroy'])->name('destroy');
        });

        // Himbauan Resource
        Route::prefix('himbauan')->name('himbauan.')->group(function () {
            Route::get('/', [HimbauanController::class, 'indexHimbauan'])->name('index');
            Route::post('/', [HimbauanController::class, 'storeHimbauan'])->name('store');
            Route::get('{id}', [HimbauanController::class, 'showHimbauan'])->name('show');
            Route::put('{id}', [HimbauanController::class, 'updateHimbauan'])->name('update');
            Route::delete('{id}', [HimbauanController::class, 'destroy'])->name('destroy');
        });
    });

    /**
     * ==========================
     * ANGGOTA REGU
     * Role: anggota_regu
     * ==========================
     */
    Route::middleware('role:super_admin|anggota_regu')->group(function () {
        Route::prefix('progress')->name('progress.')->group(function () {
            Route::get('/', [RegulationProgressController::class, 'listregulasi'])->name('list');
            Route::get('{id}', [RegulationProgressController::class, 'detailregulasi'])->name('detail');
            Route::post('/', [RegulationProgressController::class, 'catatprogresbacaan'])->name('catat');
        });

        // Penanda (Bookmark)
        Route::prefix('penanda')->name('penanda.')->group(function () {
            Route::get('/', [RegulationProgressController::class, 'bookmartregulasi'])->name('list');
            Route::get('{id}', [RegulationProgressController::class, 'detailbookmark'])->name('detail');
            Route::post('pasal', [RegulationProgressController::class, 'tandaiPasal'])->name('pasal');
            Route::post('halaman', [RegulationProgressController::class, 'tandaiHalaman'])->name('halaman');
            Route::delete('{id}', [RegulationProgressController::class, 'DestroyPenanda'])->name('destroy');
        });

        // Detail PDF
        Route::get('detail-pdf/{id}', [RegulationProgressController::class, 'detailPdf'])->name('detail-pdf');
    });

    /**
     * ==========================
     * KOMANDAN REGU
     * Role: komandan_regu
     * ==========================
     */
    Route::middleware('role:super_admin|komandan_regu')->prefix('list-validasi')->name('list-validasi.')->group(function () {
        Route::get('/', [LaporanHarianController::class, 'getValidasi'])->name('list-validasi');
        Route::put('/{id}', [LaporanHarianController::class, 'pressesValidasi'])->name('proses-validasi');
    });

    /**
     * ==========================
     * MONITORING LITERASI
     * Role: komandan_regu | super_admin
     * ==========================
     */
    Route::middleware('role:komandan_regu|super_admin')
        ->get('monitoring-literasi', [RegulationProgressController::class, 'monitoringLiterasi'])
        ->name('monitoring-literasi');
});

<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Anggota\JabatanController;
use App\Http\Controllers\Api\Anggota\AnggotaController;
use App\Http\Controllers\Api\Anggota\UnitController;
use App\Http\Controllers\Api\DokumenRegulasi\RegulasiController;
use App\Http\Controllers\Api\DokumenRegulasi\RegulationProgressController;
use App\Http\Controllers\Api\Pengaduan\KategoriPengaduanController;
use App\Http\Controllers\Api\Pengaduan\PengaduanController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum', 'role:super_admin')->group(function () {
    Route::post('register', [AuthController::class, 'register']);

    Route::get('jabatan', [JabatanController::class, 'index']);
    Route::post('jabatan', [JabatanController::class, 'store']);
    Route::get('jabatan/{id}', [JabatanController::class, 'show']);
    Route::put('jabatan/{id}', [JabatanController::class, 'update']);
    Route::delete('jabatan/{id}', [JabatanController::class, 'destroy']);

    Route::get('unit', [UnitController::class, 'index']);
    Route::post('unit', [UnitController::class, 'store']);
    Route::get('unit/{id}', [UnitController::class, 'show']);
    Route::put('unit/{id}', [UnitController::class, 'update']);
    Route::delete('unit/{id}', [UnitController::class, 'destroy']);

    Route::get('anggota', [AnggotaController::class, 'index']);
    Route::post('anggota', [AnggotaController::class, 'store']);
    Route::get('anggota/{id}', [AnggotaController::class, 'show']);
    Route::put('anggota/{id}', [AnggotaController::class, 'update']);
    Route::delete('anggota/{id}', [AnggotaController::class, 'destroy']);

    Route::get('kategori-pengaduan', [KategoriPengaduanController::class, 'index']);
    Route::post('kategori-pengaduan', [KategoriPengaduanController::class, 'store']);
    Route::get('kategori-pengaduan/{id}', [KategoriPengaduanController::class, 'show']);
    Route::put('kategori-pengaduan/{id}', [KategoriPengaduanController::class, 'update']);
    Route::delete('kategori-pengaduan/{id}', [KategoriPengaduanController::class, 'destroy']);


    Route::get('pengaduan', [PengaduanController::class, 'index']);
    Route::post('pengaduan', [PengaduanController::class, 'store']);
    Route::get('pengaduan/{id}', [PengaduanController::class, 'show']);
    Route::put('pengaduan/{id}', [PengaduanController::class, 'update']);
    Route::delete('pengaduan/{id}', [PengaduanController::class, 'destroy']);

    Route::get('regulasi', [RegulasiController::class, 'index']);
    Route::post('regulasi', [RegulasiController::class, 'store']);
    Route::get('regulasi/{id}', [RegulasiController::class, 'show']);
    Route::put('regulasi/{id}', [RegulasiController::class, 'update']);
    Route::delete('regulasi/{id}', [RegulasiController::class, 'destroy']);

    Route::get('getprogres', [RegulationProgressController::class, 'getProgress']);
    Route::post('progres', [RegulationProgressController::class, 'Progress']);
    Route::post('penanda', [RegulationProgressController::class, 'Penanda']);
    Route::get('penanda/{id}', [RegulationProgressController::class, 'GetPenanda']);
    Route::put('penanda/{id}', [RegulationProgressController::class, 'UpdatePenanda']);
    Route::delete('penanda/{id}', [RegulationProgressController::class, 'DestroyPenanda']);

});

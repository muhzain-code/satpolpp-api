<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Anggota\JabatanController;
use App\Http\Controllers\Api\Anggota\UnitController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum', 'role:super_admin')->group(function () {
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
});

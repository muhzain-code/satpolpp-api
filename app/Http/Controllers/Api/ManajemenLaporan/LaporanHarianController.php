<?php

namespace App\Http\Controllers\Api\ManajemenLaporan;

use App\Http\Controllers\Controller;
use App\Http\Requests\ManajemenLaporan\AccByKomandanRequest;
use App\Http\Requests\ManajemenLaporan\LaporanHarianRequest;
use App\Services\ManajemenLaporan\LaporanHarianService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;


class LaporanHarianController extends Controller
{
    use ApiResponse;

    protected LaporanHarianService $service;

    public function __construct(LaporanHarianService $service)
    {
        $this->service = $service;
    }

    /**
     * Menampilkan daftar laporan (Filter, Pagination, Role check via Service)
     * Endpoint: GET /api/laporan-harian
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 25);
        $currentPage = $request->input('page', 1);

        // Service menangani filter: unit_id, jenis, severity, urgensi, status_validasi, dll.
        $result = $this->service->getAll((int) $perPage, (int) $currentPage, $request);

        return $this->successResponse($result['data'], $result['message']);
    }

    /**
     * Membuat laporan baru
     * Endpoint: POST /api/laporan-harian
     */
    public function store(LaporanHarianRequest $request): JsonResponse
    {
        $result = $this->service->store($request->validated());

        // Return 201 Created
        return $this->successResponse($result['data'], $result['message'], 201);
    }

    /**
     * Menampilkan detail laporan
     * Endpoint: GET /api/laporan-harian/{id}
     */
    public function show(string $id): JsonResponse
    {
        $result = $this->service->getById($id);
        return $this->successResponse($result['data'], $result['message']);
    }

    /**
     * Update laporan (Hanya jika belum disetujui / Superadmin)
     * Endpoint: PUT/PATCH /api/laporan-harian/{id}
     */
    public function update(LaporanHarianRequest $request, string $id): JsonResponse
    {
        $result = $this->service->update($id, $request->validated());
        return $this->successResponse($result['data'], $result['message']);
    }

    /**
     * Hapus laporan
     * Endpoint: DELETE /api/laporan-harian/{id}
     */
    public function destroy(string $id): JsonResponse
    {
        $result = $this->service->delete($id);
        // Data null karena delete biasanya tidak mengembalikan objek deleted
        return $this->successResponse(null, $result['message']);
    }

    /**
     * Validasi Komandan (Setujui / Tolak / Revisi)
     * Endpoint: PUT /api/laporan-harian/{id}/validasi-komandan
     */
    public function validasiKomandan(AccByKomandanRequest $request, string $id): JsonResponse
    {
        // Memanggil method validasiKomandan di service
        $result = $this->service->validasiKomandan($id, $request->validated());

        return $this->successResponse($result['data'], $result['message']);
    }
}

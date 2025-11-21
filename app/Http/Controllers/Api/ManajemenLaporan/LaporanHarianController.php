<?php

namespace App\Http\Controllers\Api\ManajemenLaporan;

use App\Http\Controllers\Controller;
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
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 25);
        $currentPage = $request->input('page', 1);
        $result = $this->service->index($perPage, $currentPage);
        return $this->successResponse($result['data'], $result['message']);
    }

    public function store(LaporanHarianRequest $request): JsonResponse
    {
        $result = $this->service->store($request->validated());
        return $this->successResponse($result['data'], $result['message']);
    }

    public function show(string $id): JsonResponse
    {
        $result = $this->service->getById($id);
        return $this->successResponse($result['data'], $result['message']);
    }

    public function update(LaporanHarianRequest $request, string $id): JsonResponse
    {
        $result = $this->service->update($id, $request->validated());
        return $this->successResponse($result['data'], $result['message']);
    }

    public function destroy(string $id): JsonResponse
    {
        $result = $this->service->delete($id);
        return $this->successResponse($result['message']);
    }

    public function getallLaporan(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 25);
        $currentPage = $request->input('page', 1);
        $result = $this->service->getallLaporan($perPage, $currentPage, $request);

        return $this->successResponse($result['data'], $result['message']);
    }
}

<?php

namespace App\Http\Controllers\Api\Humas;

use App\Http\Controllers\Controller;
use App\Http\Requests\Humas\BeritaRequest;
use App\Services\Humas\BeritaService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BeritaController extends Controller
{
    use ApiResponse;

    protected BeritaService $service;

    public function __construct(BeritaService $service)
    {
        $this->service = $service;
    }
    public function indexBerita(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 25);
        $currentPage = $request->input('page', 1);
        $result = $this->service->listBerita($currentPage, $perPage);
        return $this->successResponse($result['data'], $result['message']);
    }

    public function store(BeritaRequest $request): JsonResponse
    {
        $result = $this->service->createBerita($request->validated());
        return $this->successResponse($result['data'], $result['message']);
    }

    public function show(string $slug): JsonResponse
    {
        $result = $this->service->showBeritaById($slug);
        return $this->successResponse($result['data'], $result['message']);
    }

    public function update(BeritaRequest $request, string $slug): JsonResponse
    {
        $result = $this->service->updateBeritaById($request->validated(), $slug);
        return $this->successResponse($result['data'], $result['message']);
    }

    public function destroy(string $slug): JsonResponse
    {
        $result = $this->service->deleteBeritaById($slug);
        return $this->successResponse($result['message']);
    }

    public function beritaPublik(Request $request): JsonResponse
    {
        $result = $this->service->KontenPublik($request);
        return $this->successResponse($result['data'], $result['message']);
    }

    public function detailKonten($slug): JsonResponse
    {
        $result = $this->service->detailKonten($slug);
        return $this->successResponse($result['data'], $result['message']);
    }
}

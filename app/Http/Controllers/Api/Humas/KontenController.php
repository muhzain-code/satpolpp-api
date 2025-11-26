<?php

namespace App\Http\Controllers\Api\Humas;

use App\Http\Controllers\Controller;
use App\Http\Requests\Humas\KontenRequest;
use App\Services\Humas\KontenService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KontenController extends Controller
{
    use ApiResponse;

    protected KontenService $service;

    public function __construct(KontenService $service)
    {
        $this->service = $service;
    }
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 25);
        $currentPage = $request->input('page', 1);
        $result = $this->service->listKonten($currentPage, $perPage);
        return $this->successResponse($result['data'], $result['message']);
    }

    public function store(KontenRequest $request): JsonResponse
    {
        $result = $this->service->createKonten($request->validated());
        return $this->successResponse($result['data'], $result['message']);
    }

    public function show(string $slug): JsonResponse
    {
        $result = $this->service->showKontenById($slug);
        return $this->successResponse($result['data'], $result['message']);
    }

    public function update(KontenRequest $request, string $slug): JsonResponse
    {
        $result = $this->service->updateKontenById($request->validated(), $slug);
        return $this->successResponse($result['data'], $result['message']);
    }

    public function destroy(string $slug): JsonResponse
    {
        $result = $this->service->deleteKontenById($slug);
        return $this->successResponse($result['message']);
    }

    public function KontenPublik(Request $request): JsonResponse
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

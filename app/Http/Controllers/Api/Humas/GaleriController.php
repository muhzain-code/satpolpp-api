<?php

namespace App\Http\Controllers\Api\Humas;

use App\Http\Controllers\Controller;
use App\Http\Requests\Humas\GaleriRequest;
use App\Services\Humas\GaleriService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GaleriController extends Controller
{

    use ApiResponse;

    protected GaleriService $service;

    public function __construct(GaleriService $service)
    {
        $this->service = $service;
    }
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 25);
        $currentPage = $request->input('page', 1);
        $result = $this->service->ambildaftargaleri($currentPage, $perPage);
        return $this->successResponse($result['data'], $result['message']);
    }

    public function store(GaleriRequest $request): JsonResponse
    {
        $result = $this->service->simpangaleribaru($request->validated());
        return $this->successResponse($result['data'], $result['message']);
    }

    public function show(string $Id): JsonResponse
    {
        $result = $this->service->ambildetailgaleri($Id);
        return $this->successResponse($result['data'], $result['message']);
    }

    public function update(GaleriRequest $request, string $Id)
    {
        $result = $this->service->perbaruidatagaleri($request->validated(), $Id);
        return $this->successResponse($result['data'], $result['message']);
    }

    public function destroy(string $Id)
    {
        $result = $this->service->hapusgaleri($Id);
        return $this->successResponse($result['message']);
    }

    public function galeripublic(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 25);
        $currentPage = $request->input('page', 1);
        $result = $this->service->ambilGaleriPublik($currentPage, $perPage);
        return $this->successResponse($result['data'], $result['message']);
    }
}

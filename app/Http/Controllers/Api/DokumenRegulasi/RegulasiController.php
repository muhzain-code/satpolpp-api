<?php

namespace App\Http\Controllers\Api\DokumenRegulasi;

use App\Http\Controllers\Controller;
use App\Http\Requests\DokumenRegulasi\RegulasiRequest;
use App\Services\DokumenRegulasi\RegulasiService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RegulasiController extends Controller
{
    use ApiResponse;
    protected RegulasiService $service;

    public function __construct(RegulasiService $service)
    {
        $this->service = $service;
    }
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 25);
        $currentPage = $request->input('page', 1);
        $result = $this->service->getAll($perPage, $currentPage);
        return $this->successResponse($result['data'], $result['message']);
    }
    public function store(RegulasiRequest $request): JsonResponse
    {
        $result = $this->service->store($request->validated());
        return $this->successResponse($result['data'], $result['message']);
    }
    public function show(string $id): JsonResponse
    {
        $result = $this->service->getByid($id);
        return $this->successResponse($result['data'], $result['message']);
    }

    public function update(RegulasiRequest $request, string $id): JsonResponse
    {
        $result = $this->service->update($id, $request->validated());
        return $this->successResponse($result['data'], $result['message']);
    }

    public function destroy($id): JsonResponse
    {
        $result = $this->service->delete($id);
        return $this->successResponse($result['message']);
    }
    public function GetallProgress(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 25);
        $currentPage = $request->input('page', 1);
        $result = $this->service->GetallProgress($perPage, $currentPage, $request);
        return $this->successResponse($result['data'], $result['message']);
    }
}

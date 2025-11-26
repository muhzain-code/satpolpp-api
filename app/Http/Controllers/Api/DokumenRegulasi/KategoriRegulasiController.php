<?php

namespace App\Http\Controllers\Api\DokumenRegulasi;

use App\Http\Controllers\Controller;
use App\Http\Requests\DokumenRegulasi\KategoriRegulasiRequest;
use App\Services\DokumenRegulasi\KategoriRegulasiService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KategoriRegulasiController extends Controller
{
    use ApiResponse;
    protected KategoriRegulasiService $service;

    public function __construct(KategoriRegulasiService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 25);
        $currentPage = $request->input('page', 1);
        $filters = [
            'keyword' => $request->input('keyword', null),
        ];

        $result = $this->service->getall($filters, $perPage, $currentPage);
        return $this->successResponse($result['data'], $result['message']);
    }

    public function store(KategoriRegulasiRequest $request): JsonResponse
    {
        $result = $this->service->create($request->validated());
        return $this->successResponse($result['data'], $result['message']);
    }

    public function show(string $id): JsonResponse
    {
        $result = $this->service->getbyId($id);
        return $this->successResponse($result['data'], $result['message']);
    }

    public function update(KategoriRegulasiRequest $request, string $id): JsonResponse
    {
        $result = $this->service->update($id, $request->validated());
        return $this->successResponse($result['data'], $result['message']);
    }

    public function destroy($id): JsonResponse
    {
        $result = $this->service->delete($id);
        return $this->successResponse($result['message']);
    }
}

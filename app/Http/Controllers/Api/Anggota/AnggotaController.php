<?php

namespace App\Http\Controllers\Api\Anggota;

use App\Exceptions\CustomException;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Models\Anggota\Anggota;
use App\Http\Controllers\Controller;
use App\Http\Requests\Anggota\AnggotaRequest;
use App\Services\Anggota\AnggotaService;
use Illuminate\Http\JsonResponse;

class AnggotaController extends Controller
{
    use ApiResponse;

    protected AnggotaService $service;

    public function __construct(AnggotaService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 25);
        $currentPage = $request->input('page', 1);
        $result = $this->service->getAll($perPage, $currentPage);
        return $this->successResponse($result['data'], $result['message']);
    }

    public function show($id)
    {
        $result = $this->service->getById($id);
        return $this->successResponse($result['data'], $result['message']);
    }

    public function store(AnggotaRequest $request): JsonResponse
    {
        $result = $this->service->create($request->validated());
        return $this->successResponse($result['data'], $result['message']);
    }

    public function update(AnggotaRequest $request, $id): JsonResponse
    {
        $result = $this->service->update($request->validated(), $id);
        return $this->successResponse($result['data'], $result['message']);
    }

    public function destroy($id): JsonResponse
    {
        $result = $this->service->delete($id);
        return $this->successResponse(null, $result['message']);
    }
}

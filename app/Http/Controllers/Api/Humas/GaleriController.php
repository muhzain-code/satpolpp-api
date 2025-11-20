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
        $result = $this->service->index($currentPage, $perPage);
        return $this->successResponse($result['data'], $result['message']);
    }

    public function store(GaleriRequest $request): JsonResponse
    {
        $result = $this->service->store($request->validated());
        return $this->successResponse($result['data'], $result['message']);
    }

    public function show(string $Id): JsonResponse
    {
        $result = $this->service->show($Id);
        return $this->successResponse($result['data'], $result['message']);
    }

    public function update(GaleriRequest $request, string $Id)
    {
        $result = $this->service->update($request->validated(), $Id);
        return $this->successResponse($result['data'], $result['message']);
    }

    public function destroy(string $Id)
    {
        $result = $this->service->destroy($Id);
        return $this->successResponse($result['message']);
    }
}

<?php

namespace App\Http\Controllers\Api\Humas;

use App\Http\Controllers\Controller;
use App\Http\Requests\Humas\HimbauanRequest;
use App\Services\Humas\HimbauanService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HimbauanController extends Controller
{

    use ApiResponse;

    protected HimbauanService $service;

    public function __construct(HimbauanService $service)
    {
        $this->service = $service;
    }
    public function indexHimbauan(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 25);
        $currentPage = $request->input('page', 1);
        $search = $request->input('search'); // Service himbauan Anda support search

        $result = $this->service->listHimbauan($currentPage, $perPage, $search);
        return $this->successResponse($result['data'], $result['message']);
    }

    public function storeHimbauan(HimbauanRequest $request): JsonResponse
    {
        $result = $this->service->createHimbauan($request->validated());
        return $this->successResponse($result['data'], $result['message']);
    }

    public function showHimbauan($id): JsonResponse
    {
        $result = $this->service->showHimbauanById($id);
        return $this->successResponse($result['data'], $result['message']);
    }

    public function updateHimbauan(HimbauanRequest $request, $id): JsonResponse
    {
        $result = $this->service->updateHimbauanById($request->validated(), $id);
        return $this->successResponse($result['data'], $result['message']);
    }
    public function destroy(string $id): JsonResponse
    {
        $result = $this->service->deleteHimbauanById($id);
        return $this->successResponse($result['message']);
    }
    public function himbauanPublik(Request $request): JsonResponse
    {
        $result = $this->service->himbauanPublik($request);
        return $this->successResponse($result['data'], $result['message']);
    }

    public function detailKonten($slug): JsonResponse
    {
        $result = $this->service->detailHimbauan($slug);
        return $this->successResponse($result['data'], $result['message']);
    }
}

<?php

namespace App\Http\Controllers\Api\PPID;

use App\Http\Controllers\Controller;
use App\Http\Requests\PPID\LacakPPIDRequest;
use App\Http\Requests\PPID\PPIDRequest;
use App\Http\Requests\PPID\ValidasiPPIDRequest;
use App\Services\PPID\PPIDService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PPIDController extends Controller
{
    use ApiResponse;

    protected PPIDService $service;

    public function __construct(PPIDService $service)
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

    public function permohonanPPID(PPIDRequest $request): JsonResponse
    {
        $result = $this->service->permohonanPPID($request->validated());
        return $this->successResponse($result['data'], $result['message']);
    }

    public function lacakPPID(LacakPPIDRequest $request): JsonResponse
    {
        $result = $this->service->lacakPPID($request->validated());
        return $this->successResponse($result['data'], $result['message']);
    }

    public function validasiPPID(ValidasiPPIDRequest $request, $Id): JsonResponse
    {
        $result = $this->service->validasiPPID($request->validated(), $Id);
        return $this->successResponse($result['data'], $result['message']);

    }
}

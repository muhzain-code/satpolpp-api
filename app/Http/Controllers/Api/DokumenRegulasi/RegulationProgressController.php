<?php

namespace App\Http\Controllers\Api\DokumenRegulasi;

use App\Http\Controllers\Controller;
use App\Http\Requests\DokumenRegulasi\CatatanPenandaRequest;
use App\Http\Requests\DokumenRegulasi\PenandaRequest;
use App\Http\Requests\DokumenRegulasi\ProgressRequest;
use App\Services\DokumenRegulasi\RegulationProgressService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RegulationProgressController extends Controller
{
    use ApiResponse;
    protected RegulationProgressService $service;

    public function __construct(RegulationProgressService $service)
    {
        $this->service = $service;
    }
    public function getProgress(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 25);
        $currentPage = $request->input('page', 1);
        $result = $this->service->getProgress($perPage, $currentPage);
        return $this->successResponse($result['data'], $result['message']);
    }

    public function Progress(ProgressRequest $request): JsonResponse
    {
        $result = $this->service->progress($request->validated());
        return $this->successResponse($result['data'], $result['message']);
    }

    public function Penanda(PenandaRequest $request): JsonResponse
    {
        $result = $this->service->penanda($request->validated());
        return $this->successResponse($result['data'], $request['message']);
    }

    public function GetPenanda(string $Id): JsonResponse
    {
        $result = $this->service->GetPenanda($Id);
        return $this->successResponse($result['data'], $result['message']);
    }
    public function UpdatePenanda(CatatanPenandaRequest $request, $Id): JsonResponse
    {
        $result = $this->service->UpdatePenanda($request->validated(), $Id);
        return $this->successResponse($result['data'], $result['message']);
    }
    public function DestroyPenanda($Id): JsonResponse
    {
        $result = $this->service->destroyPenanda($Id);
        return $this->successResponse($result['message']);
    }
}

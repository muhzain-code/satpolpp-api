<?php

namespace App\Http\Controllers\Api\DokumenRegulasi;

use App\Http\Controllers\Controller;
use App\Http\Requests\DokumenRegulasi\CatatanPenandaRequest;
use App\Http\Requests\DokumenRegulasi\PenandaHalamanRequest;
use App\Http\Requests\DokumenRegulasi\PenandaPasalRequest;
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
    public function listregulasi(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 25);
        $currentPage = $request->input('page', 1);
        $result = $this->service->listbacaan($perPage, $currentPage);
        return $this->successResponse($result['data'], $result['message']);
    }

    public function detailregulasi($id): JsonResponse
    {
        $result = $this->service->detailbacaan($id);
        return $this->successResponse($result['data'], $result['message']);
    }

    public function catatprogresbacaan(ProgressRequest $request): JsonResponse
    {
        $result = $this->service->catatbacaan($request->validated());
        return $this->successResponse($result['data'], $result['message']);
    }

    public function bookmartregulasi(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 25);
        $currentPage = $request->input('page', 1);
        $result = $this->service->listtanda($perPage, $currentPage);
        return $this->successResponse($result['data'], $result['message']);
    }

    public function detailbookmark($id): JsonResponse
    {
        $result = $this->service->detailtanda($id);
        return $this->successResponse($result['data'], $result['message']);
    }

    public function tandaiPasal(PenandaPasalRequest $request): JsonResponse
    {
        $result = $this->service->buatPenandaPasal($request->validated());
        return $this->successResponse($result['data'], $result['message']);
    }

    public function updatetandaiPasal(PenandaPasalRequest $request, $id): JsonResponse
    {
        $result = $this->service->perbaruiPenandaPasal($id, $request->validated());
        return $this->successResponse($result['data'], $result['message']);
    }

    public function tandaiHalaman(PenandaHalamanRequest $request): JsonResponse
    {
        $result = $this->service->buatPenandaHalaman($request->validated());
        return $this->successResponse($result['data'], $result['message']);
    }

    public function updatetandaihalaman(PenandaHalamanRequest $request, $id): JsonResponse
    {
        $result = $this->service->perbaruiPenandaHalaman($id, $request->validated());
        return $this->successResponse($result['data'], $result['message']);
    }

    public function DestroyPenanda($Id): JsonResponse
    {
        $result = $this->service->deletePenanda($Id);
        return $this->successResponse($result['message']);
    }

    public function monitoringLiterasi(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 10);
        $currentPage = $request->input('page', 1);
        $filters = $request->only(['filter_type', 'date', 'month', 'status']);
        $result = $this->service->monitoringLiterasi($perPage, $currentPage, $filters);
        return $this->successResponse($result['data'], $result['message']);
    }
}

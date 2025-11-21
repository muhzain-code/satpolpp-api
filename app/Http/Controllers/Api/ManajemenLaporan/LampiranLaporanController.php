<?php

namespace App\Http\Controllers\Api\ManajemenLaporan;

use App\Http\Controllers\Controller;
use App\Http\Requests\ManajemenLaporan\AccByKomandanRequest;
use App\Http\Requests\ManajemenLaporan\LaporanHarianAnggotaRequest;
use App\Http\Requests\ManajemenLaporan\LaporanHarianAnggotaUppRequest;
use App\Services\ManajemenLaporan\LampiranLaporanService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LampiranLaporanController extends Controller
{
    use ApiResponse;
    protected LampiranLaporanService $service;

    public function __construct(LampiranLaporanService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 25);
        $currentPage = $request->input('page', 1);
        $result = $this->service->getLaporanAnggota($perPage, $currentPage);
        return $this->successResponse($result['data'], $result['message']);
    }

    public function show($Id): JsonResponse
    {
        $result = $this->service->GetByidLapAnggota($Id);
        return $this->successResponse($result['data'], $result['message']);
    }

    public function store(LaporanHarianAnggotaRequest $request): JsonResponse
    {
        $result = $this->service->StoreLapAnggota($request->validated());
        return $this->successResponse($result['data'], $result['message']);
    }

    public function update(LaporanHarianAnggotaUppRequest $request, $Id): JsonResponse
    {
        $result = $this->service->UpdateLapAnggota($Id, $request->validated());
        return $this->successResponse($result['data'], $result['message']);
    }
    public function indexKomandan(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 25);
        $currentPage = $request->input('page', 1);
        $result = $this->service->GetKomandanLaporan($perPage, $currentPage);
        return $this->successResponse($result['data'], $result['message']);
    }
    public function AccbyKomandan(AccByKomandanRequest $request, $Id): JsonResponse
    {
        $result = $this->service->AccBykomandan($Id, $request->validated());
        return $this->successResponse($result['data'], $result['message']);
    }
}

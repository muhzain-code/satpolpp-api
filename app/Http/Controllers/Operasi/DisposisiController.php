<?php

namespace App\Http\Controllers\Operasi;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\Operasi\DisposisiRequest;
use GrahamCampbell\ResultType\Success;
use App\Services\Operasi\DisposisiService;

class DisposisiController extends Controller
{
    use ApiResponse;
    protected DisposisiService $service;

    public function __construct(DisposisiService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 25);
        $currentPage = $request->input('page', 1);
        $pengaduanId = $request->input('pengaduan_id');
        $keUnitId = $request->input('ke_unit_id');
        $keAnggotaId = $request->input('ke_anggota_id');

        $result = $this->service->getAll([$perPage, $currentPage, $pengaduanId, $keUnitId, $keAnggotaId]);
        return $this->successResponse($result['data'], $result['message']);
    }

    public function store(DisposisiRequest $request)
    {
        $result = $this->service->create($request->validated());
        return $this->successResponse($result['data'], $result['message']);
    }

    public function show($id)
    {
        $result = $this->service->getById($id);
        return $this->successResponse($result['data'], $result['message']);
    }

    public function update(DisposisiRequest $request, $id)
    {
        $result = $this->service->update($request->validated(), $id);
        return $this->successResponse($result['data'], $result['message']);
    }

    public function destroy($id)
    {
        $result = $this->service->delete($id);
        return $this->successResponse($result['data'], $result['message']);
    }
}

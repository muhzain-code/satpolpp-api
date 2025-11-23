<?php

namespace App\Http\Controllers\Api\Operasi;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Operasi\OperasiPenugasanService;
use App\Http\Requests\Operasi\StoreOperasiPenugasanRequest;
use App\Http\Requests\Operasi\UpdateOperasiPenugasanRequest;

class OperasiPenugasanController extends Controller
{
    use ApiResponse;

    protected OperasiPenugasanService $service;

    public function __construct(OperasiPenugasanService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $filter = [
            'per_page'   => $request->input('per_page', 25),
            'page'       => $request->input('page', 1),
            'operasi_id' => $request->input('operasi_id'),
            'anggota_id' => $request->input('anggota_id'),
            'peran'      => $request->input('peran'),
        ];

        $result = $this->service->getAll($filter);

        return $this->successResponse($result['data'], $result['message']);
    }

    public function store(StoreOperasiPenugasanRequest $request)
    {
        $result = $this->service->create($request->validated());

        return $this->successResponse($result['data'], $result['message']);
    }

    public function show($id)
    {
        $result = $this->service->getById($id);

        return $this->successResponse($result['data'], $result['message']);
    }

    public function update(UpdateOperasiPenugasanRequest $request, $id)
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

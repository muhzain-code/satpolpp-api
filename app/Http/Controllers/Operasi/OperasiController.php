<?php

namespace App\Http\Controllers\Operasi;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Operasi\OperasiService;
use App\Http\Requests\Operasi\OperasiRequest;
use App\Http\Requests\Operasi\StoreOperasiRequest;
use App\Http\Requests\Operasi\UpdateOperasiRequest;

class OperasiController extends Controller
{
    use ApiResponse;

    protected OperasiService $service;

    public function __construct(OperasiService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $filter = [
            'per_page'      => $request->input('per_page', 25),
            'page'          => $request->input('page', 1),
            'pengaduan_id'  => $request->input('pengaduan_id'),
            'mulai'         => $request->input('mulai'),
            'selesai'       => $request->input('selesai'),
            'keyword'       => $request->input('keyword'),
        ];

        $result = $this->service->getAll($filter);
        return $this->successResponse($result['data'], $result['message']);
    }

    public function store(StoreOperasiRequest $request)
    {
        $result = $this->service->create($request->validated());
        return $this->successResponse($result['data'], $result['message']);
    }

    public function show($id)
    {
        $result = $this->service->getById($id);
        return $this->successResponse($result['data'], $result['message']);
    }

    public function update(UpdateOperasiRequest $request, $id)
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

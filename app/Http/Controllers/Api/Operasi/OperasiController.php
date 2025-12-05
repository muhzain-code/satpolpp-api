<?php

namespace App\Http\Controllers\Api\Operasi;

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

        $request->input('per_page', 25);
        $request->input('page', 1);

        $result = $this->service->getAll($request);

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

    public function getOperasiAnggota()
    {
        $result = $this->service->getOperasiAnggota();
        return $this->successResponse($result['data'], $result['message']);
    }
}

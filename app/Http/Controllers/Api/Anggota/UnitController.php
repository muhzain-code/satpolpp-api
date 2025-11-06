<?php

namespace App\Http\Controllers\Api\Anggota;

use App\Traits\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Anggota\StoreUnitRequest;
use App\Services\Anggota\UnitService;

class UnitController extends Controller
{
    use ApiResponse;
    protected UnitService $service;

    public function __construct(UnitService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        $result = $this->service->getAll();
        return $this->successResponse($result['data'], $result['message']);
    }

    public function store(StoreUnitRequest $request)
    {
        $result = $this->service->create($request->validated());
        return $this->successResponse($result['data'], $result['message'], 201);
    }

    public function show($id)
    {
        $result = $this->service->getById($id);
        return $this->successResponse($result['data'], $result['message']);
    }


    public function update(StoreUnitRequest $request, $id)
    {
        $result = $this->service->update($id, $request->validated());
        return $this->successResponse($result['data'], $result['message']);
    }

    public function destroy($id)
    {
        $result = $this->service->delete($id);
        return $this->successResponse(null, $result['message']);
    }
}

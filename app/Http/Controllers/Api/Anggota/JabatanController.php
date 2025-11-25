<?php

namespace App\Http\Controllers\Api\Anggota;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Anggota\JabatanService;
use App\Http\Requests\Anggota\StoreJabatanRequest;
use App\Http\Requests\Anggota\UpdateJabatanRequest;

class JabatanController extends Controller
{
    use ApiResponse;
    protected JabatanService $service;

    public function __construct(JabatanService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 25); 
        $currentPage = $request->input('page', 1);
        $result = $this->service->getAll($perPage, $currentPage);
        return $this->successResponse($result['data'], $result['message']);
    }

    public function store(StoreJabatanRequest $request)
    {
        $result = $this->service->create($request->validated());
        return $this->successResponse($result['data'], $result['message'], 201);
    }

    public function show($id)
    {
        $result = $this->service->getById($id);
        return $this->successResponse($result['data'], $result['message']);
    }


    public function update(UpdateJabatanRequest $request, $id)
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

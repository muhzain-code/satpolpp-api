<?php

namespace App\Http\Controllers\Api\Penindakan;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\Penindakan\PenindakanRequest;
use App\Http\Requests\Penindakan\ValidasiKomandanRequest;
use App\Services\Penindakan\PenindakanService;
use App\Http\Requests\Penindakan\ValidasiPpnsRequest;

class PenindakanController extends Controller
{
    use ApiResponse;
    protected PenindakanService $service;

    public function __construct(PenindakanService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $request->merge([
            'per_page' => $request->input('per_page', 25),
            'page' => $request->input('page', 1),
        ]);

        $result = $this->service->getAll($request->all());
        return $this->successResponse($result['data'], $result['message']);
    }

    public function store(PenindakanRequest $request)
    {
        $result = $this->service->create($request->validated());
        return $this->successResponse($result['data'], $result['message']);
    }

    public function show($id)
    {
        $result = $this->service->getById($id);
        return $this->successResponse($result['data'], $result['message']);
    }

    public function update(PenindakanRequest $request, $id)
    {
        $result = $this->service->update($id, $request->validated());
        return $this->successResponse($result['data'], $result['message']);
    }

    public function destroy($id)
    {
        $result = $this->service->delete($id);
        return $this->successResponse($result['data'], $result['message']);
    }

    public function validasiPPNS(ValidasiPpnsRequest $request, $id)
    {
        $result = $this->service->validasiPPNS($id, $request->validated());
        return $this->successResponse($result['data'], $result['message']);
    }

    public function validasiKomandan(ValidasiKomandanRequest $request, $id)
    {
        $result = $this->service->validasiKomandan($id, $request->validated());
        return $this->successResponse($result['data'], $result['message']);
    }
}

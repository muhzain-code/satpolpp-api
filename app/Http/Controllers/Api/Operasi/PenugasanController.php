<?php

namespace App\Http\Controllers\Api\Operasi;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\Operasi\PenugasanRequest;
use App\Services\Operasi\PenugasanService;

class PenugasanController extends Controller
{
    use ApiResponse;

    protected PenugasanService $service;

    public function __construct(PenugasanService $service)
    {
        $this->service = $service;
    }

    public function store(PenugasanRequest $request)
    {
        $result = $this->service->create($request->validated());
        return $this->successResponse($result['data'], $result['message']);
    }

    public function update(PenugasanRequest $request, $id)
    {
        $result = $this->service->update($id, $request->validated());
        return $this->successResponse($result['data'], $result['message']);
    }
}

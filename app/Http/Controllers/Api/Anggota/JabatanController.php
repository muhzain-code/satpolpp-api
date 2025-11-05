<?php

namespace App\Http\Controllers\Api\Anggota;

use App\Http\Controllers\Controller;
use App\Http\Requests\Anggota\StoreJabatanRequest;
use App\Http\Requests\Anggota\UpdateJabatanRequest;
use App\Services\Anggota\JabatanService;
use Illuminate\Http\Request;

class JabatanController extends Controller
{
    protected JabatanService $service;

    public function __construct(JabatanService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        $result = $this->service->getAll();
        return successResponse($result['data'], $result['message']);
    }

    public function store(StoreJabatanRequest $request)
    {
        $result = $this->service->create($request->validated());
        return successResponse($result['data'], $result['message'], 201);
    }

    public function show($id)
    {
        $result = $this->service->getById($id);

        if (!$result['status']) {
            return errorResponse(null, $result['message'], 404);
        }

        return successResponse($result['data'], $result['message']);
    }


    public function update(UpdateJabatanRequest $request, $id)
    {
        $result = $this->service->update($id, $request->validated());
        if (!$result['status']) {
            return errorResponse(null, $result['message'], 404);
        }
        return successResponse($result['data'], $result['message']);
    }

    public function destroy($id)
    {
        $result = $this->service->delete($id);
        if (!$result['status']) {
            return errorResponse(null, $result['message'], 404);
        }
        return successResponse(null, $result['message']);
    }
}

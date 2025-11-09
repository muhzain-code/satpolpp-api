<?php

namespace App\Http\Controllers\Api\Pengaduan;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pengaduan\PengaduanRequest;
use App\Services\Pengaduan\PengaduanService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PengaduanController extends Controller
{
    use ApiResponse;

    protected PengaduanService $service;

    public function __construct(PengaduanService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 25);
        $page = $request->input('page', 1);
        $status = $request->input('status');
        $kategoriId = $request->input('kategori_id');

        $result = $this->service->getAll([
            'per_page' => $perPage,
            'page' => $page,
            'status' => $status,
            'kategori_id' => $kategoriId,
        ]);
        return $this->successResponse($result['data'], $result['message']);
    }

    public function store(PengaduanRequest $request): JsonResponse
    {
        $result = $this->service->create($request->validated());
        return $this->successResponse($result['data'], $result['message']);
    }
}

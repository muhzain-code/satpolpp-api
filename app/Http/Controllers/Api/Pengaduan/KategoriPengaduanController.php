<?php

namespace App\Http\Controllers\Api\Pengaduan;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pengaduan\KategoriPengaduanRequest;
use App\Http\Requests\Pengaduan\UpdateKategoriPengaduanRequest;
use App\Services\Pengaduan\KategoriPengaduanService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class KategoriPengaduanController extends Controller
{
    use ApiResponse;

    protected KategoriPengaduanService $service;

    public function __construct(KategoriPengaduanService $service)
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

    public function store(KategoriPengaduanRequest $request)
    {
        $result = $this->service->create($request->validated());
        return $this->successResponse($result['data'], $result['message'], 201);
    }

    public function show($id)
    {
        $result = $this->service->getById($id);
        return $this->successResponse($result['data'], $result['message']);
    }

    public function update(UpdateKategoriPengaduanRequest $request, $id)
    {
        $result = $this->service->update($id, $request->validated());
        return $this->successResponse($result['data'], $result['message']);
    }

    /**
     * Hapus kategori pengaduan
     */
    public function destroy($id)
    {
        $result = $this->service->delete($id);
        return $this->successResponse(null, $result['message']);
    }
}

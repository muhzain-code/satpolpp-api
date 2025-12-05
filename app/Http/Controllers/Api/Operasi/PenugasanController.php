<?php

namespace App\Http\Controllers\Api\Operasi;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\Operasi\PenugasanRequest;
use App\Http\Requests\Operasi\PenugasanUpdateRequest;
use App\Services\Operasi\PenugasanService;

class PenugasanController extends Controller
{
    use ApiResponse;

    protected PenugasanService $service;

    public function __construct(PenugasanService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        // 1. Ambil parameter pagination (default per_page 10, page 1)
        $perPage = $request->input('per_page', 10);
        $currentPage = $request->input('page', 1);

        // 2. Ambil filter yang diizinkan (disposisi_id & operasi_id)
        $filters = $request->only(['disposisi_id', 'operasi_id']);

        // 3. Panggil service
        $result = $this->service->getAll($perPage, $currentPage, $filters);

        // 4. Return response
        return $this->successResponse($result['data'], $result['message']);
    }

    /**
     * Menampilkan detail satu penugasan (Get By ID)
     */
    public function show($id)
    {
        // Service sudah menangani logic 404 dan hak akses
        $result = $this->service->getById($id);

        return $this->successResponse($result['data'], $result['message']);
    }
    public function listAnggotaPenugasan($id)
    {
        // Service sudah menangani logic 404 dan hak akses
        $result = $this->service->listAnggotaPenugasan($id);

        return $this->successResponse($result['data'], $result['message']);
    }

    /**
     * Membuat penugasan baru (Create)
     */
    public function store(PenugasanRequest $request)
    {
        $result = $this->service->create($request->validated());

        // Biasanya create mengembalikan status 201
        return $this->successResponse($result['data'], $result['message'], 201);
    }

    /**
     * Memperbarui penugasan (Update)
     */
    public function update(PenugasanUpdateRequest $request, $id)
    {
        $result = $this->service->update($id, $request->validated());

        return $this->successResponse($result['data'], $result['message']);
    }

    /**
     * Menghapus penugasan (Delete)
     */
    public function destroy($id)
    {
        $result = $this->service->delete($id);

        // Data null karena delete biasanya tidak mengembalikan objek data
        return $this->successResponse(null, $result['message']);
    }
}

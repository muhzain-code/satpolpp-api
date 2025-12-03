<?php

namespace App\Http\Controllers\Api\Humas;

use App\Http\Controllers\Controller;
use App\Http\Requests\Humas\AgendaRequest;
use App\Http\Requests\Humas\HimbauanRequest;
use App\Http\Requests\Humas\KontenRequest;
use App\Services\Humas\KontenService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KontenController extends Controller
{
    use ApiResponse;

    protected KontenService $service;

    public function __construct(KontenService $service)
    {
        $this->service = $service;
    }
    public function indexBerita(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 25);
        $currentPage = $request->input('page', 1);
        $result = $this->service->listBerita($currentPage, $perPage);
        return $this->successResponse($result['data'], $result['message']);
    }

    public function store(KontenRequest $request): JsonResponse
    {
        $result = $this->service->createBerita($request->validated());
        return $this->successResponse($result['data'], $result['message']);
    }

    public function show(string $slug): JsonResponse
    {
        $result = $this->service->showBeritaById($slug);
        return $this->successResponse($result['data'], $result['message']);
    }

    public function update(KontenRequest $request, string $slug): JsonResponse
    {
        $result = $this->service->updateBeritaById($request->validated(), $slug);
        return $this->successResponse($result['data'], $result['message']);
    }

    public function indexAgenda(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 25);
        $currentPage = $request->input('page', 1);
        $keyword = $request->input('search');
        $result = $this->service->listAgenda($currentPage, $perPage, $keyword);

        return $this->successResponse($result['data'], $result['message']);
    }

    public function storeAgenda(AgendaRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['tampilkan_publik'] = $request->boolean('tampilkan_publik');

        $result = $this->service->createAgenda($data);

        return $this->successResponse($result['data'], $result['message']);
    }

    public function showAgenda($id): JsonResponse
    {
        $result = $this->service->showAgendaById($id);
        return $this->successResponse($result['data'], $result['message']);
    }

    public function updateAgenda(AgendaRequest $request, $id): JsonResponse
    {
        $data = $request->validated();
        $data['tampilkan_publik'] = $request->boolean('tampilkan_publik');
        $result = $this->service->updateAgendaById($data, $id);

        return $this->successResponse($result['data'], $result['message']);
    }


    public function indexHimbauan(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 25);
        $currentPage = $request->input('page', 1);
        $search = $request->input('search'); // Service himbauan Anda support search

        $result = $this->service->listHimbauan($currentPage, $perPage, $search);
        return $this->successResponse($result['data'], $result['message']);
    }

    public function storeHimbauan(HimbauanRequest $request): JsonResponse
    {
        $result = $this->service->createHimbauan($request->validated());
        return $this->successResponse($result['data'], $result['message']);
    }

    public function showHimbauan($id): JsonResponse
    {
        $result = $this->service->showHimbauanById($id);
        return $this->successResponse($result['data'], $result['message']);
    }

    public function updateHimbauan(HimbauanRequest $request, $id): JsonResponse
    {
        $result = $this->service->updateHimbauanById($request->validated(), $id);
        return $this->successResponse($result['data'], $result['message']);
    }
    public function destroy(string $slug): JsonResponse
    {
        $result = $this->service->deleteKontenById($slug);
        return $this->successResponse($result['message']);
    }

    public function KontenPublik(Request $request): JsonResponse
    {
        $result = $this->service->KontenPublik($request);
        return $this->successResponse($result['data'], $result['message']);
    }

    public function detailKonten($slug): JsonResponse
    {
        $result = $this->service->detailKonten($slug);
        return $this->successResponse($result['data'], $result['message']);
    }
}

<?php

namespace App\Http\Controllers\Api\Humas;

use App\Http\Controllers\Controller;
use App\Http\Requests\Humas\AgendaRequest;
use App\Services\Humas\AgendaService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgendaController extends Controller
{
    use ApiResponse;

    protected AgendaService $service;

    public function __construct(AgendaService $service)
    {
        $this->service = $service;
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


    public function destroy(string $slug): JsonResponse
    {
        $result = $this->service->deleteAgendaById($slug);
        return $this->successResponse($result['message']);
    }

    public function agendaPublik(Request $request): JsonResponse
    {
        $result = $this->service->agendaPublik($request);
        return $this->successResponse($result['data'], $result['message']);
    }
}

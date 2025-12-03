<?php

namespace App\Http\Controllers\Api\DokumenRegulasi;

use App\Http\Controllers\Controller;
use App\Http\Requests\DokumenRegulasi\RegulasiRequest;
use App\Services\DokumenRegulasi\RegulasiService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RegulasiController extends Controller
{
    use ApiResponse;
    protected RegulasiService $service;

    public function __construct(RegulasiService $service)
    {
        $this->service = $service;
    }
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 25);
        $currentPage = $request->input('page', 1);
        $filters = [
            'keyword' => $request->input('keyword', null),
            'tahun'   => $request->input('tahun', null),
            'jenis'   => $request->input('jenis', null),
        ];

        $result = $this->service->getall($filters, $perPage, $currentPage);
        return $this->successResponse($result['data'], $result['message']);
    }
    public function store(RegulasiRequest $request): JsonResponse
    {
        $result = $this->service->create($request->validated());
        return $this->successResponse($result['data'], $result['message']);
    }
    public function show(string $id): JsonResponse
    {
        $result = $this->service->getbyid($id);
        return $this->successResponse($result['data'], $result['message']);
    }

    public function update(RegulasiRequest $request, string $id): JsonResponse
    {
        $result = $this->service->update($id, $request->validated());
        return $this->successResponse($result['data'], $result['message']);
    }

    public function destroy($id): JsonResponse
    {
        $result = $this->service->delete($id);
        return $this->successResponse($result['message']);
    }

    public function regulasiPublik(Request $request): JsonResponse
    {
        $result = $this->service->regulasiPublik($request);
        return $this->successResponse($result['data'], $result['message']);
    }

    public function kategoriregulasi(): JsonResponse
    {
        $result = $this->service->filteringRegulasi();
        return $this->successResponse($result['data'], $result['message']);
    }

    public function showPdf(Request $request)
    {
        $path = $request->query('path');
        if (!$path) abort(404);

        $cleanPath = ltrim(Str::replaceFirst('storage/', '', $path), '/');
        $fullPath = storage_path('app/public/' . $cleanPath);

        if (!file_exists($fullPath)) {
            return response()->json(['message' => 'File not found'], 404);
        }

        $headers = [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, OPTIONS',
            'Access-Control-Allow-Headers' => '*',
            'Cache-Control' => 'public, max-age=3600',
        ];

        return response()->file($fullPath, $headers);
    }
}

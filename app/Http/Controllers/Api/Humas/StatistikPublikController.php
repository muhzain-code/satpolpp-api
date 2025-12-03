<?php

namespace App\Http\Controllers\Api\Humas;

use App\Http\Controllers\Controller;
use App\Services\Humas\StatistikPublikService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StatistikPublikController extends Controller
{
    protected StatistikPublikService $service;

    public function __construct(StatistikPublikService $service)
    {
        $this->service = $service;
    }

    public function index(): JsonResponse
    {
        $data = $this->service->getDashboardData();

        return response()->json([
            'status' => 'success',
            'message' => 'Data statistik operasi berhasil diambil',
            'data' => $data
        ]);
    }

    public function indexMonth(): JsonResponse
    {
        $data = $this->service->getStatistikBulanIni();

        return response()->json([
            'status' => 'success',
            'message' => 'Data statistik operasi berhasil diambil',
            'data' => $data
        ]);
    }
}

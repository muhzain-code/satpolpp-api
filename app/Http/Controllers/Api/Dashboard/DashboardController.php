<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Dashboard\DashboardService;

class DashboardController extends Controller
{
    use ApiResponse;

    protected $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    public function index()
    {
        $result = $this->dashboardService->getDashboardStats();

        // Menggunakan Trait successResponse sesuai request Anda
        return $this->successResponse($result['data'], $result['message']);
    }
}
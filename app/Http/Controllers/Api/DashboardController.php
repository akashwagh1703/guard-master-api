<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\DashboardResource;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboardService) {}

    public function index(Request $request): JsonResponse
    {
        $data = $this->dashboardService->getDashboard($request->query());

        return $this->success($data, 'Dashboard data retrieved successfully.');
    }
}

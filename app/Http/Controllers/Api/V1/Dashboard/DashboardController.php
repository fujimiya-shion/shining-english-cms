<?php

namespace App\Http\Controllers\Api\V1\Dashboard;

use App\Http\Controllers\Api\ApiController;
use App\Services\Dashboard\IDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends ApiController
{
    public function __construct(
        protected IDashboardService $dashboardService,
    ) {}

    public function overview(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = $this->dashboardService->overview((int) $user->id);

        return $this->success(data: $data->toArray());
    }
}

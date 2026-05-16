<?php

namespace App\Services\Dashboard;

use App\DTO\Dashboard\DashboardOverviewResponse;

interface IDashboardService
{
    public function overview(int $userId): DashboardOverviewResponse;
}

<?php

namespace App\Services\Dashboard;

interface IDashboardService
{
    /**
     * @return array<string, mixed>
     */
    public function overview(int $userId): array;
}


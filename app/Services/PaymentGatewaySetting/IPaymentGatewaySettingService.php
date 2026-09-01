<?php

declare(strict_types=1);

namespace App\Services\PaymentGatewaySetting;

use App\Models\PaymentGatewaySetting;
use App\Services\IService;

interface IPaymentGatewaySettingService extends IService
{
    public function getBySlug(string $slug): ?PaymentGatewaySetting;

    public function toggleActive(string $slug): PaymentGatewaySetting;

    public function getActiveSettings(string $slug): ?array;
}

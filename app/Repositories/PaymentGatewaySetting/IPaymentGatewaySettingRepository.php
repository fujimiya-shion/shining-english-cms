<?php

declare(strict_types=1);

namespace App\Repositories\PaymentGatewaySetting;

use App\Models\PaymentGatewaySetting;
use App\Repositories\IRepository;

interface IPaymentGatewaySettingRepository extends IRepository
{
    public function findBySlug(string $slug): ?PaymentGatewaySetting;
}

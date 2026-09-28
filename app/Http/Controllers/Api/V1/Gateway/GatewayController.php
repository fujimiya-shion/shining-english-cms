<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Gateway;

use App\Http\Controllers\Api\ApiController;
use App\Services\IService;
use App\Services\PaymentGatewaySetting\IPaymentGatewaySettingService;
use App\Traits\ApiBehaviour;

class GatewayController extends ApiController
{
    use ApiBehaviour;
    public function __construct(
        private IPaymentGatewaySettingService $service,
    ) {}

    protected function service(): IService {
        return $this->service;
    }
}

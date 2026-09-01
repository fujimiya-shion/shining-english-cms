<?php

declare(strict_types=1);

namespace App\Repositories\PaymentGatewaySetting;

use App\Models\PaymentGatewaySetting;
use App\Repositories\Repository;

class PaymentGatewaySettingRepository extends Repository implements IPaymentGatewaySettingRepository
{
    public function __construct(PaymentGatewaySetting $model)
    {
        parent::__construct($model);
    }

    public function findBySlug(string $slug): ?PaymentGatewaySetting
    {
        return $this->model->newQuery()->where('slug', $slug)->first();
    }
}

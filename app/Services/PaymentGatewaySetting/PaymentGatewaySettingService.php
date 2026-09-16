<?php

declare(strict_types=1);

namespace App\Services\PaymentGatewaySetting;

use App\Models\PaymentGatewaySetting;
use App\Repositories\PaymentGatewaySetting\IPaymentGatewaySettingRepository;
use App\Services\Service;
use RuntimeException;

class PaymentGatewaySettingService extends Service implements IPaymentGatewaySettingService
{
    protected IPaymentGatewaySettingRepository $paymentGatewaySettingRepository;

    public function __construct(IPaymentGatewaySettingRepository $repository)
    {
        parent::__construct($repository);
        $this->paymentGatewaySettingRepository = $repository;
    }

    public function getBySlug(string $slug): ?PaymentGatewaySetting
    {
        return $this->paymentGatewaySettingRepository->findBySlug($slug);
    }

    public function toggleActive(string $slug): PaymentGatewaySetting
    {
        $setting = $this->paymentGatewaySettingRepository->findBySlug($slug);

        if (! $setting instanceof PaymentGatewaySetting) {
            throw new RuntimeException("Payment gateway setting [{$slug}] not found.");
        }

        $this->paymentGatewaySettingRepository->update((int) $setting->id, [
            'is_active' => ! $setting->is_active,
        ]);

        PaymentGatewaySetting::clearCache($slug);

        return $setting->refresh();
    }

    public function getActiveSettings(string $slug): ?array
    {
        return PaymentGatewaySetting::getActiveSettings($slug);
    }
}

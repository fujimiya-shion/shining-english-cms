<?php

declare(strict_types=1);
use App\Filament\Resources\PaymentGatewaySettings\PaymentGatewaySettingResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePaymentGatewaySetting extends CreateRecord {
    protected static string $resource = PaymentGatewaySettingResource::class;
}
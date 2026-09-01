<?php

declare(strict_types=1);

namespace App\Filament\Resources\PaymentGatewaySettings\Pages;

use App\Enums\GatewayFieldRegistry;
use App\Enums\GatewayType;
use App\Filament\Resources\PaymentGatewaySettings\PaymentGatewaySettingResource;
use App\Models\PaymentGatewaySetting;
use Filament\Resources\Pages\EditRecord;

class EditPaymentGatewaySetting extends EditRecord
{
    protected static string $resource = PaymentGatewaySettingResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $settings = $data['settings'] ?? [];

        $gatewayType = GatewayType::tryFrom($data['slug'] ?? '');
        if ($gatewayType === null) {
            return $data;
        }

        $items = [];
        foreach ($settings as $key => $value) {
            $field = GatewayFieldRegistry::getField($gatewayType, $key);
            $items[] = [
                'key' => $key,
                'value' => (string) $value,
                'type' => $field['type'] ?? 'text',
            ];
        }

        $data['settings_fields'] = $items;

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $items = $data['settings_fields'] ?? [];
        $flat = [];
        foreach ($items as $item) {
            if (is_array($item) && isset($item['key']) && $item['key'] !== '') {
                $flat[$item['key']] = $item['value'] ?? '';
            }
        }
        $data['settings'] = $flat;
        unset($data['settings_fields']);

        return $data;
    }

    protected function afterSave(): void
    {
        PaymentGatewaySetting::clearCache($this->record->slug);
    }
}

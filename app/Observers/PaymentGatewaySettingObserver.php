<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\GatewayType;
use App\Integrations\Payments\Contracts\PaymentGatewaySettingHandler;
use App\Integrations\Payments\Handlers\PayosPaymentGatewaySettingHandler;
use App\Models\PaymentGatewaySetting;
use Filament\Notifications\Notification;

class PaymentGatewaySettingObserver
{
    public function updated(PaymentGatewaySetting $setting): void
    {
        if (! $setting->isDirty('settings')) {
            return;
        }

        $handler = $this->resolveHandler($setting->slug);
        if ($handler === null) {
            return;
        }

        $oldSettings = $setting->getOriginal('settings') ?? [];
        $newSettings = $setting->settings ?? [];

        try {
            $handler->onSettingsChanged($oldSettings, $newSettings);
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Xử lý cấu hình thất bại')
                ->body($e->getMessage())
                ->warning()
                ->send();
        }
    }

    protected function resolveHandler(string $slug): ?PaymentGatewaySettingHandler
    {
        return match ($slug) {
            GatewayType::Payos->value => app(PayosPaymentGatewaySettingHandler::class),
            default => null,
        };
    }
}

<?php

declare(strict_types=1);

namespace App\Integrations\Payments\Contracts;

interface PaymentGatewaySettingHandler
{
    /**
     * Called when gateway settings are updated.
     * The handler decides what actions to take based on old vs new settings.
     *
     * @param  array<string, mixed>  $oldSettings
     * @param  array<string, mixed>  $newSettings
     */
    public function onSettingsChanged(array $oldSettings, array $newSettings): void;
}

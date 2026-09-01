<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\GatewayType;
use App\Models\PaymentGatewaySetting;
use Illuminate\Database\Seeder;

class PaymentGatewaySettingSeeder extends Seeder
{
    public function run(): void
    {
        $gateways = [
            [
                'name' => GatewayType::Payos->label(),
                'slug' => GatewayType::Payos->value,
                'is_active' => false,
                'settings' => [
                    'webhook_url' => '',
                    'client_id' => '',
                    'api_key' => '',
                    'checksum_key' => '',
                    'base_url' => 'https://api-merchant.payos.vn',
                ],
            ],
            [
                'name' => GatewayType::Cod->label(),
                'slug' => GatewayType::Cod->value,
                'is_active' => false,
                'settings' => [
                    'instructions' => 'Thanh toán tiền mặt khi nhận hàng.',
                ],
            ],
            [
                'name' => GatewayType::Star->label(),
                'slug' => GatewayType::Star->value,
                'is_active' => false,
                'settings' => [],
            ],
        ];

        foreach ($gateways as $gateway) {
            PaymentGatewaySetting::updateOrCreate(
                ['slug' => $gateway['slug']],
                $gateway,
            );
        }
    }
}

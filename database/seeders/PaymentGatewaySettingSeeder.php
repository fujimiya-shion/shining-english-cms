<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\PaymentMethod;
use App\Models\PaymentGatewaySetting;
use Illuminate\Database\Seeder;

class PaymentGatewaySettingSeeder extends Seeder
{
    public function run(): void
    {
        $gateways = [
            [
                'factory_name' => PaymentMethod::Payos->factoryName(),
                'slug' => PaymentMethod::Payos->value,
                'is_active' => false,
                'settings' => [
                    'name' => 'Chuyển khoản ngân hàng',
                    'webhook_url' => '',
                    'client_id' => '',
                    'api_key' => '',
                    'checksum_key' => '',
                    'description' => 'Chuyển khoản ngân hàng',
                    'base_url' => 'https://api-merchant.payos.vn',
                ],
            ],
            [
                'factory_name' => PaymentMethod::Cod->factoryName(),
                'slug' => PaymentMethod::Cod->value,
                'is_active' => false,
                'settings' => [
                    'name' => 'Thanh toán tiền mặt khi nhận hàng',
                    'description' => 'Thanh toán tiền mặt khi nhận hàng.',
                ],
            ],
            [
                'factory_name' => PaymentMethod::Star->factoryName(),
                'slug' => PaymentMethod::Star->value,
                'is_active' => false,
                'settings' => [
                    'name' => 'Thanh toán bằng sao',
                    'description' => 'Thanh toán bằng sao',
                ],
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

<?php

declare(strict_types=1);

namespace App\Integrations\Payments\Handlers;

use App\Integrations\Payments\Contracts\PaymentGatewaySettingHandler;
use Illuminate\Support\Facades\Http;
use Log;

class PayosPaymentGatewaySettingHandler implements PaymentGatewaySettingHandler
{
    public function onSettingsChanged(array $oldSettings, array $newSettings): void
    {
        $oldWebhookUrl = $oldSettings['webhook_url'] ?? null;
        $newWebhookUrl = $newSettings['webhook_url'] ?? null;

        if ($oldWebhookUrl === $newWebhookUrl) {
            return;
        }

        if ($newWebhookUrl === null || $newWebhookUrl === '') {
            Log::info('PayOS webhook URL cleared, skipping registration.');

            return;
        }

        $this->registerWebhook($newWebhookUrl, $newSettings);
    }

    protected function registerWebhook(string $webhookUrl, array $settings): bool
    {
        $clientId = $settings['client_id'] ?? null;
        $apiKey = $settings['api_key'] ?? null;
        $baseUrl = $settings['base_url'] ?? 'https://api-merchant.payos.vn';

        if ($clientId === null || $apiKey === null) {
            Log::warning('PayOS webhook registration skipped: missing client_id or api_key.');

            return false;
        }

        try {
            $response = Http::acceptJson()
                ->withHeaders([
                    'x-client-id' => $clientId,
                    'x-api-key' => $apiKey,
                ])
                ->timeout(10)
                ->post(rtrim($baseUrl, '/').'/confirm-webhook', [
                    'webhookUrl' => $webhookUrl,
                ]);

            if ($response->successful()) {
                Log::info('PayOS webhook registered successfully.', [
                    'webhook_url' => $webhookUrl,
                ]);

                return true;
            }

            Log::error('PayOS webhook registration failed.', [
                'status' => $response->status(),
                'body' => $response->body(),
                'webhook_url' => $webhookUrl,
            ]);

            return false;
        } catch (\Throwable $e) {
            Log::error('PayOS webhook registration exception.', [
                'message' => $e->getMessage(),
                'webhook_url' => $webhookUrl,
            ]);

            return false;
        }
    }
}

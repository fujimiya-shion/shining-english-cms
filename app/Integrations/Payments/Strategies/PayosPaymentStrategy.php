<?php

declare(strict_types=1);

namespace App\Integrations\Payments\Strategies;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Integrations\Payments\Contracts\PaymentStrategy;
use App\Integrations\Payments\DTO\PaymentInitializationResult;
use App\Integrations\Payments\Support\PayosSignature;
use App\Models\Order;
use App\Notifications\PaymentSuccessNotification;
use App\Repositories\Order\IOrderRepository;
use App\ValueObjects\CheckoutCustomerData;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PayosPaymentStrategy implements PaymentStrategy
{
    public function __construct(
        private IOrderRepository $orderRepository,
    ) {}

    public function method(): PaymentMethod
    {
        return PaymentMethod::Payos;
    }

    public function initialize(Order $order, CheckoutCustomerData $customerData): PaymentInitializationResult
    {
        if ((int) $order->total_amount <= 0) {
            return PaymentInitializationResult::none();
        }

        $returnUrl = $this->buildReturnUrl((int) $order->id);
        $cancelUrl = $this->buildCancelUrl((int) $order->id);
        $description = $this->buildDescription((int) $order->id);

        $payload = [
            'orderCode' => (int) $order->id,
            'amount' => (int) $order->total_amount,
            'description' => $description,
            'buyerName' => $customerData->fullName,
            'buyerEmail' => $customerData->email,
            'buyerPhone' => $customerData->phone,
            'items' => $order->items->map(static function ($item): array {
                return [
                    'name' => (string) ($item->course?->name ?? 'Khoa hoc'),
                    'quantity' => (int) $item->quantity,
                    'price' => (int) $item->price,
                ];
            })->values()->all(),
            'cancelUrl' => $cancelUrl,
            'returnUrl' => $returnUrl,
            'signature' => PayosSignature::sign([
                'amount' => (int) $order->total_amount,
                'cancelUrl' => $cancelUrl,
                'description' => $description,
                'orderCode' => (int) $order->id,
                'returnUrl' => $returnUrl,
            ], $this->checksumKey()),
        ];

        $response = $this->request(
            method: 'POST',
            uri: '/v2/payment-requests',
            payload: $payload,
        );
        $body = $response->json();

        if ($response->failed() || ! is_array($body) || ($body['code'] ?? null) !== '00') {
            throw new RuntimeException($this->resolveErrorMessage($body, 'Failed to create payOS payment link.'));
        }

        $data = is_array($body['data'] ?? null) ? $body['data'] : null;
        $checkoutUrl = is_string($data['checkoutUrl'] ?? null) ? $data['checkoutUrl'] : null;
        if ($data === null || $checkoutUrl === null) {
            throw new RuntimeException('payOS did not return a checkout URL.');
        }

        $order->forceFill([
            'payment_reference' => $data['paymentLinkId'] ?? null,
            'payment_checkout_url' => $checkoutUrl,
            'payment_metadata' => array_filter([
                'provider' => 'payos',
                'provider_status' => $data['status'] ?? null,
                'qr_code' => $data['qrCode'] ?? null,
                'raw_create_link_response' => $data,
            ], static fn (mixed $value): bool => $value !== null),
        ])->save();

        return PaymentInitializationResult::redirect(
            $checkoutUrl,
            [
                'provider' => 'payos',
                'payment_link_id' => $data['paymentLinkId'] ?? null,
                'status' => $data['status'] ?? null,
            ],
        );
    }

    public function refresh(Order $order): Order
    {
        if (
            $order->payment_method !== PaymentMethod::Payos
            || ! is_string($order->payment_reference)
            || trim($order->payment_reference) === ''
        ) {
            return $order;
        }

        $response = $this->request(
            method: 'GET',
            uri: '/v2/payment-requests/'.$order->payment_reference,
        );
        $body = $response->json();
        if ($response->failed() || ! is_array($body) || ($body['code'] ?? null) !== '00') {
            return $order;
        }

        $data = is_array($body['data'] ?? null) ? $body['data'] : null;
        if ($data === null) {
            return $order;
        }

        $providerStatus = strtoupper((string) ($data['status'] ?? 'PENDING'));
        $nextStatus = $this->mapProviderStatus($providerStatus);
        $metadata = is_array($order->payment_metadata) ? $order->payment_metadata : [];
        $metadata['provider'] = 'payos';
        $metadata['provider_status'] = $providerStatus;
        $metadata['raw_get_link_response'] = $data;

        $previousStatus = $order->status;

        $order->forceFill([
            'status' => $nextStatus,
            'paid_at' => $nextStatus === OrderStatus::Paid ? ($order->paid_at ?? now()) : $order->paid_at,
            'payment_metadata' => $metadata,
        ])->save();

        $fresh = $order->fresh(['items.course', 'user']) ?? $order;

        if ($previousStatus !== OrderStatus::Paid && $nextStatus === OrderStatus::Paid) {
            $user = $fresh->relationLoaded('user') ? $fresh->user : $fresh->user()->first();
            if ($user) {
                $courseNames = $fresh->items
                    ->pluck('course.name')
                    ->filter()
                    ->implode(', ');

                $user->notify(new PaymentSuccessNotification(
                    orderId: (int) $fresh->id,
                    orderCode: (string) ($fresh->payment_reference ?? $fresh->id),
                    totalAmount: (int) $fresh->total_amount,
                    courseNames: $courseNames,
                ));
            }
        }

        return $fresh;
    }

    public function cancel(Order $order, string $reason): Order
    {
        if (
            $order->payment_method !== PaymentMethod::Payos
            || ! is_string($order->payment_reference)
            || trim($order->payment_reference) === ''
        ) {
            return $order;
        }

        $response = $this->request(
            method: 'POST',
            uri: '/v2/payment-requests/'.$order->payment_reference.'/cancel',
            payload: [
                'cancellationReason' => $reason,
            ],
        );
        $body = $response->json();
        if ($response->failed() || ! is_array($body) || ($body['code'] ?? null) !== '00') {
            throw new RuntimeException($this->resolveErrorMessage($body, 'Failed to cancel payOS payment link.'));
        }

        $data = is_array($body['data'] ?? null) ? $body['data'] : [];
        $metadata = is_array($order->payment_metadata) ? $order->payment_metadata : [];
        $metadata['provider'] = 'payos';
        $metadata['provider_status'] = 'CANCELLED';
        $metadata['raw_cancel_link_response'] = $data;

        $order->forceFill([
            'payment_metadata' => $metadata,
        ])->save();

        return $order;
    }

    public function handleWebhook(array $payload): ?Order
    {
        $signature = is_string($payload['signature'] ?? null) ? $payload['signature'] : null;
        $data = is_array($payload['data'] ?? null) ? $payload['data'] : null;

        if ($signature === null || $data === null) {
            throw new RuntimeException('Invalid payOS webhook payload.');
        }

        if (! PayosSignature::verify($data, $signature, $this->checksumKey())) {
            throw new RuntimeException('Invalid payOS webhook signature.');
        }

        $orderCode = (int) ($data['orderCode'] ?? 0);
        if ($orderCode <= 0) {
            return null;
        }

        $order = $this->orderRepository->getById($orderCode, ['items.course', 'user']);
        if (! $order instanceof Order) {
            return null;
        }

        $previousStatus = $order->status;

        $providerStatus = $this->resolveWebhookStatus($data);
        $nextStatus = $this->mapProviderStatus($providerStatus);
        $metadata = is_array($order->payment_metadata) ? $order->payment_metadata : [];
        $metadata['provider'] = 'payos';
        $metadata['provider_status'] = $providerStatus;
        $metadata['last_webhook'] = $data;

        $order->forceFill([
            'status' => $nextStatus,
            'paid_at' => $nextStatus === OrderStatus::Paid ? ($order->paid_at ?? now()) : $order->paid_at,
            'payment_reference' => $data['paymentLinkId'] ?? $order->payment_reference,
            'payment_metadata' => $metadata,
        ])->save();

        $fresh = $order->fresh(['items.course', 'user']);

        if ($fresh && $previousStatus !== OrderStatus::Paid && $nextStatus === OrderStatus::Paid && $fresh->user) {
            $courseNames = $fresh->items
                ->pluck('course.name')
                ->filter()
                ->implode(', ');

            $fresh->user->notify(new PaymentSuccessNotification(
                orderId: (int) $fresh->id,
                orderCode: (string) ($fresh->payment_reference ?? $fresh->id),
                totalAmount: (int) $fresh->total_amount,
                courseNames: $courseNames,
            ));
        }

        return $fresh;
    }

    /**
     * @return array<string, string>
     */
    private function headers(): array
    {
        return [
            'x-client-id' => (string) config('payos.client_id'),
            'x-api-key' => (string) config('payos.api_key'),
        ];
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('payos.base_url'), '/');
    }

    private function checksumKey(): string
    {
        $checksumKey = (string) config('payos.checksum_key');
        if ($checksumKey === '') {
            throw new RuntimeException('PAYOS_CHECKSUM_KEY is missing.');
        }

        return $checksumKey;
    }

    private function buildReturnUrl(int $orderCode): string
    {
        return rtrim((string) config('app.frontend_app_url'), '/').'/payment/success?orderCode='.$orderCode;
    }

    private function buildCancelUrl(int $orderCode): string
    {
        return rtrim((string) config('app.frontend_app_url'), '/').'/payment/fail?orderCode='.$orderCode;
    }

    private function buildDescription(int $orderCode): string
    {
        return substr('SE'.$orderCode, 0, 9);
    }

    private function mapProviderStatus(string $providerStatus): OrderStatus
    {
        return match ($providerStatus) {
            'PAID' => OrderStatus::Paid,
            'CANCELLED' => OrderStatus::Cancelled,
            default => OrderStatus::Pending,
        };
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveWebhookStatus(array $data): string
    {
        $code = strtoupper((string) ($data['code'] ?? ''));
        $desc = strtoupper((string) ($data['desc'] ?? ''));

        if ($code === '00') {
            return 'PAID';
        }

        if (str_contains($desc, 'CANCEL')) {
            return 'CANCELLED';
        }

        return 'PENDING';
    }

    /**
     * @param  array<string, mixed>|null  $body
     */
    private function resolveErrorMessage(?array $body, string $fallback): string
    {
        $message = $body['desc'] ?? $body['message'] ?? null;

        return is_string($message) && trim($message) !== '' ? $message : $fallback;
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    private function request(string $method, string $uri, ?array $payload = null): Response
    {
        $request = Http::acceptJson()
            ->withHeaders($this->headers());

        $url = $this->baseUrl().$uri;

        return $method === 'GET'
            ? $request->get($url)
            : $request->send($method, $url, ['json' => $payload ?? []]);
    }
}

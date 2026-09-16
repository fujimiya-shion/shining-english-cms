<?php

declare(strict_types=1);

namespace App\Integrations\Payments\Contracts;

use App\Enums\PaymentMethod;
use App\Integrations\Payments\DTO\PaymentInitializationResult;
use App\Models\Order;
use App\ValueObjects\CheckoutCustomerData;

interface PaymentStrategy
{
    public function method(): PaymentMethod;

    public function initialize(Order $order, CheckoutCustomerData $customerData): PaymentInitializationResult;

    public function repay(Order $order, CheckoutCustomerData $customerData): PaymentInitializationResult;

    public function refresh(Order $order): Order;

    public function cancel(Order $order, string $reason): Order;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handleWebhook(array $payload): ?Order;
}

<?php

declare(strict_types=1);

namespace App\Integrations\Payments\Strategies;

use App\Enums\PaymentMethod;
use App\Integrations\Payments\Contracts\PaymentStrategy;
use App\Integrations\Payments\DTO\PaymentInitializationResult;
use App\Models\Order;
use App\ValueObjects\CheckoutCustomerData;

class CodPaymentStrategy implements PaymentStrategy
{
    public function method(): PaymentMethod
    {
        return PaymentMethod::Cod;
    }

    public function initialize(Order $order, CheckoutCustomerData $customerData): PaymentInitializationResult
    {
        return PaymentInitializationResult::none();
    }

    public function refresh(Order $order): Order
    {
        return $order;
    }

    public function cancel(Order $order, string $reason): Order
    {
        return $order;
    }

    public function handleWebhook(array $payload): ?Order
    {
        return null;
    }

    public function repay(Order $order, CheckoutCustomerData $customerData): PaymentInitializationResult
    {
        return PaymentInitializationResult::none();
    }
}

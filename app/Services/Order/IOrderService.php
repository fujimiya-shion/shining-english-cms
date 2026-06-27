<?php

namespace App\Services\Order;

use App\DTO\Transaction\Checkout\CheckoutOrderResponse;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Services\IService;
use App\ValueObjects\CheckoutCustomerData;
use App\ValueObjects\QueryOption;
use Illuminate\Pagination\LengthAwarePaginator;

interface IOrderService extends IService
{
    public function listByUserId(int $userId, QueryOption $options): LengthAwarePaginator;

    public function detailByUserId(int $userId, int $orderId): ?Order;

    public function createFromCart(
        int $userId,
        PaymentMethod $paymentMethod,
        CheckoutCustomerData $customerData,
    ): CheckoutOrderResponse;

    public function createBuyNow(
        int $userId,
        int $courseId,
        int $quantity,
        PaymentMethod $paymentMethod,
        CheckoutCustomerData $customerData,
    ): CheckoutOrderResponse;

    public function cancelByUserId(int $userId, int $orderId): bool;

    public function createWithStarPayment(int $userId, int $courseId): Order;
}

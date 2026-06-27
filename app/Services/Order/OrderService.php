<?php

namespace App\Services\Order;

use App\DTO\Transaction\Checkout\CheckoutOrderResponse;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Integrations\Payments\Factories\PaymentStrategyFactory;
use App\Models\Order;
use App\Repositories\Cart\ICartRepository;
use App\Repositories\Course\ICourseRepository;
use App\Repositories\Order\IOrderRepository;
use App\Repositories\OrderItem\IOrderItemRepository;
use App\Services\Enrollment\IEnrollmentService;
use App\Services\Service;
use App\ValueObjects\CheckoutCustomerData;
use App\ValueObjects\QueryOption;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class OrderService extends Service implements IOrderService
{
    protected IOrderRepository $orderRepository;

    protected IOrderItemRepository $orderItemRepository;

    protected ICartRepository $cartRepository;

    protected ICourseRepository $courseRepository;

    protected IEnrollmentService $enrollmentService;

    public function __construct(
        IOrderRepository $repository,
        IOrderItemRepository $orderItemRepository,
        ICartRepository $cartRepository,
        ICourseRepository $courseRepository,
        IEnrollmentService $enrollmentService,
    ) {
        parent::__construct($repository);
        $this->orderRepository = $repository;
        $this->orderItemRepository = $orderItemRepository;
        $this->cartRepository = $cartRepository;
        $this->courseRepository = $courseRepository;
        $this->enrollmentService = $enrollmentService;
    }

    public function listByUserId(int $userId, QueryOption $options): LengthAwarePaginator
    {
        return $this->orderRepository->paginateByUserId($userId, $options);
    }

    public function detailByUserId(int $userId, int $orderId): ?Order
    {
        $order = $this->orderRepository->findByUserId($userId, $orderId);

        if (! $order) {
            return null;
        }

        return $this->resolveStrategy($order->payment_method)->refresh($order);
    }

    public function createFromCart(
        int $userId,
        PaymentMethod $paymentMethod,
        CheckoutCustomerData $customerData,
    ): CheckoutOrderResponse {
        $items = $this->cartRepository->itemsByUserId($userId);

        if ($items->isEmpty()) {
            throw new RuntimeException('Cart is empty');
        }

        return DB::transaction(function () use ($customerData, $paymentMethod, $userId, $items): CheckoutOrderResponse {
            $total = $items->sum(fn ($item): int => $item->course->price * $item->quantity);
            $courseIds = $items->pluck('course_id')->unique()->values()->all();
            $order = $this->createOrderRecord($userId, $total, $paymentMethod);

            foreach ($items as $item) {
                $this->orderItemRepository->create([
                    'order_id' => $order->id,
                    'course_id' => $item->course_id,
                    'quantity' => $item->quantity,
                    'price' => $item->course->price,
                ]);
            }

            $this->cartRepository->clearByUserId($userId);

            DB::afterCommit(function () use ($courseIds, $order, $userId): void {
                foreach ($courseIds as $courseId) {
                    $this->enrollmentService->enroll($userId, (int) $courseId, $order->id);
                }
            });

            return $this->finalizeCheckout($order, $customerData);
        });
    }

    public function createBuyNow(
        int $userId,
        int $courseId,
        int $quantity,
        PaymentMethod $paymentMethod,
        CheckoutCustomerData $customerData,
    ): CheckoutOrderResponse {
        $course = $this->courseRepository->getById($courseId);

        if (! $course) {
            throw new RuntimeException('Course not found');
        }

        return DB::transaction(function () use ($course, $customerData, $paymentMethod, $quantity, $userId): CheckoutOrderResponse {
            $total = (int) $course->price * $quantity;
            $order = $this->createOrderRecord($userId, $total, $paymentMethod);

            $this->orderItemRepository->create([
                'order_id' => $order->id,
                'course_id' => $course->id,
                'quantity' => $quantity,
                'price' => $course->price,
            ]);

            DB::afterCommit(function () use ($userId, $course, $order): void {
                $this->enrollmentService->enroll($userId, $course->id, $order->id);
            });

            return $this->finalizeCheckout($order, $customerData);
        });
    }

    public function cancelByUserId(int $userId, int $orderId): bool
    {
        $order = $this->orderRepository->findByUserId($userId, $orderId);

        if (! $order) {
            return false;
        }

        $this->resolveStrategy($order->payment_method)->cancel($order, 'Cancelled by user.');
        $order->status = OrderStatus::Cancelled;
        $order->save();

        return true;
    }

    public function createWithStarPayment(int $userId, int $courseId): Order
    {
        $course = $this->courseRepository->getById($courseId);

        if (! $course) {
            throw new RuntimeException('Course not found');
        }

        if (! (bool) ($course->allow_star_payment ?? false)) {
            throw new RuntimeException('Course does not support star payment');
        }

        return DB::transaction(function () use ($userId, $course): Order {
            $order = $this->createOrderRecord($userId, 0, PaymentMethod::Star);

            $this->orderItemRepository->create([
                'order_id' => $order->id,
                'course_id' => $course->id,
                'quantity' => 1,
                'price' => 0,
            ]);

            DB::afterCommit(function () use ($userId, $course, $order): void {
                $this->enrollmentService->enroll($userId, $course->id, $order->id);
            });

            return $order;
        });
    }

    private function createOrderRecord(int $userId, int $total, PaymentMethod $paymentMethod): Order
    {
        $initialStatus = $total <= 0 ? OrderStatus::Paid : OrderStatus::Pending;

        /** @var Order $order */
        $order = $this->orderRepository->create([
            'user_id' => $userId,
            'total_amount' => $total,
            'status' => $initialStatus,
            'payment_method' => $paymentMethod,
            'placed_at' => now(),
            'paid_at' => $initialStatus === OrderStatus::Paid ? now() : null,
        ]);

        return $order;
    }

    private function finalizeCheckout(Order $order, CheckoutCustomerData $customerData): CheckoutOrderResponse
    {
        $order = $order->fresh(['items.course']) ?? $order->load(['items.course']);
        $paymentResult = $this->resolveStrategy($order->payment_method)->initialize($order, $customerData);

        return new CheckoutOrderResponse(
            order: $order->fresh(['items.course']) ?? $order,
            paymentAction: $paymentResult->toCheckoutAction(),
        );
    }

    private function resolveStrategy(PaymentMethod|string|null $paymentMethod): \App\Integrations\Payments\Contracts\PaymentStrategy
    {
        $method = $paymentMethod instanceof PaymentMethod
            ? $paymentMethod
            : (PaymentMethod::tryFrom((string) $paymentMethod) ?? PaymentMethod::Cod);

        return PaymentStrategyFactory::make($method);
    }
}

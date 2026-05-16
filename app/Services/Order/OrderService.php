<?php

namespace App\Services\Order;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Repositories\Cart\ICartRepository;
use App\Repositories\Course\ICourseRepository;
use App\Repositories\Order\IOrderRepository;
use App\Repositories\OrderItem\IOrderItemRepository;
use App\Services\Enrollment\IEnrollmentService;
use App\Services\Service;
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
        return $this->orderRepository->findByUserId($userId, $orderId);
    }

    public function createFromCart(int $userId, PaymentMethod $paymentMethod): Order
    {
        $items = $this->cartRepository->itemsByUserId($userId);

        if ($items->isEmpty()) {
            throw new RuntimeException('Cart is empty');
        }

        return DB::transaction(function () use ($userId, $items, $paymentMethod): Order {
            $total = $items->sum(fn ($item): int => $item->course->price * $item->quantity);
            $courseIds = $items->pluck('course_id')->unique()->values()->all();
            $initialStatus = $total <= 0 ? OrderStatus::Paid : OrderStatus::Pending;

            /** @var Order $order */
            $order = $this->orderRepository->create([
                'user_id' => $userId,
                'total_amount' => $total,
                'status' => $initialStatus,
                'payment_method' => $paymentMethod,
                'placed_at' => now(),
            ]);

            foreach ($items as $item) {
                $this->orderItemRepository->create([
                    'order_id' => $order->id,
                    'course_id' => $item->course_id,
                    'quantity' => $item->quantity,
                    'price' => $item->course->price,
                ]);
            }

            $this->cartRepository->clearByUserId($userId);

            DB::afterCommit(function () use ($userId, $courseIds, $order): void {
                foreach ($courseIds as $courseId) {
                    $this->enrollmentService->enroll($userId, (int) $courseId, $order->id);
                }
            });

            return $order->load(['items.course']);
        });
    }

    public function createBuyNow(int $userId, int $courseId, int $quantity, PaymentMethod $paymentMethod): Order
    {
        $course = $this->courseRepository->getById($courseId);

        if (! $course) {
            throw new RuntimeException('Course not found');
        }

        return DB::transaction(function () use ($userId, $course, $quantity, $paymentMethod): Order {
            $total = (int) $course->price * $quantity;
            $initialStatus = $total <= 0 ? OrderStatus::Paid : OrderStatus::Pending;

            /** @var Order $order */
            $order = $this->orderRepository->create([
                'user_id' => $userId,
                'total_amount' => $total,
                'status' => $initialStatus,
                'payment_method' => $paymentMethod,
                'placed_at' => now(),
            ]);

            $this->orderItemRepository->create([
                'order_id' => $order->id,
                'course_id' => $course->id,
                'quantity' => $quantity,
                'price' => $course->price,
            ]);

            DB::afterCommit(function () use ($userId, $course, $order): void {
                $this->enrollmentService->enroll($userId, $course->id, $order->id);
            });

            return $order->load(['items.course']);
        });
    }

    public function cancelByUserId(int $userId, int $orderId): bool
    {
        $order = $this->orderRepository->findByUserId($userId, $orderId);

        if (! $order) {
            return false;
        }

        $order->status = OrderStatus::Cancelled;
        $order->save();

        return true;
    }
}

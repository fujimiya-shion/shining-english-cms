<?php

namespace App\Http\Controllers\Api\V1\Transaction;

use App\Enums\PaymentMethod;
use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\Transaction\OrderStoreRequest;
use App\Services\Order\IOrderService;
use App\Traits\Jsonable;
use App\ValueObjects\CheckoutCustomerData;
use App\ValueObjects\MetaPagination;
use App\ValueObjects\QueryOption;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class OrderController extends ApiController
{
    use Jsonable;

    public function __construct(
        protected IOrderService $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $options = QueryOption::fromArray($request->all(), true);

        $paginator = $this->service->listByUserId($user->id, $options);
        $meta = MetaPagination::fromLengthAwarePaginator($paginator);

        return $this->success(
            data: $paginator->getCollection(),
            meta: $meta->toArray(),
        );
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $order = $this->service->detailByUserId($user->id, $id);

        if (! $order) {
            return $this->notfound('Order not found');
        }

        return $this->success(data: $order);
    }

    public function store(OrderStoreRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();
        $type = $data['type'];
        $paymentMethod = PaymentMethod::tryFrom($data['payment_method'] ?? 'cod') ?? PaymentMethod::Cod;

        try {
            $customerData = CheckoutCustomerData::fromArray($data);

            if ($type === 'cart') {
                $checkout = $this->service->createFromCart($user->id, $paymentMethod, $customerData);
            } else {
                $checkout = $this->service->createBuyNow(
                    $user->id,
                    (int) $data['course_id'],
                    (int) ($data['quantity'] ?? 1),
                    $paymentMethod,
                    $customerData,
                );
            }

            return $this->created($checkout->toArray(), 'Order created');
        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $cancelled = $this->service->cancelByUserId($user->id, $id);

        if (! $cancelled) {
            return $this->notfound('Order not found');
        }

        return $this->success('Order cancelled');
    }
}

<?php

namespace App\Http\Controllers\Api\V1\Cart;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\Cart\CartStoreRequest;
use App\Services\Cart\ICartService;
use App\Traits\Jsonable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class CartController extends ApiController
{
    use Jsonable;

    public function __construct(
        protected ICartService $service
    ) {}

    public function items(Request $request): JsonResponse
    {
        $user = $request->user();
        $items = $this->service->itemsByUserId($user->id);

        return $this->success(data: $items);
    }

    public function store(CartStoreRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        try {
            $this->service->addCourse(
                $user->id,
                (int) $data['course_id'],
                (int) ($data['quantity'] ?? 1),
            );
        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        }

        return $this->created([
            'course_id' => (int) $data['course_id'],
            'enrolled' => false,
            'pending_access' => false,
            'in_cart' => true,
        ], 'Course added to cart');
    }

    public function count(Request $request): JsonResponse
    {
        $user = $request->user();
        $counts = $this->service->countByUserId($user->id);

        return $this->success(data: $counts);
    }

    public function clear(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->service->clearByUserId($user->id);

        return $this->deleted('Cart cleared');
    }
}

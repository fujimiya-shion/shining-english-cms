<?php

namespace App\Repositories\Order;

use App\Models\Order;
use App\Repositories\Repository;
use App\ValueObjects\QueryOption;
use Illuminate\Pagination\LengthAwarePaginator;

class OrderRepository extends Repository implements IOrderRepository
{
    protected function getDefaultOrderBy(): string
    {
        return 'order';
    }

    protected function getDefaultOrderDirection(): string
    {
        return 'asc';
    }

    public function __construct(Order $model)
    {
        parent::__construct($model);
    }

    public function paginateByUserId(int $userId, QueryOption $options): LengthAwarePaginator
    {
        return $this->paginateBy(['user_id' => $userId], $options);
    }

    public function findByUserId(int $userId, int $orderId): ?Order
    {
        return $this->model
            ->newQuery()
            ->with(['items.course'])
            ->where('user_id', $userId)
            ->where('id', $orderId)
            ->first();
    }
}

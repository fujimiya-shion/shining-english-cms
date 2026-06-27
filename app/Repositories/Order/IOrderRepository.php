<?php

namespace App\Repositories\Order;

use App\Models\Order;
use App\Repositories\IRepository;
use App\ValueObjects\QueryOption;
use Illuminate\Pagination\LengthAwarePaginator;

interface IOrderRepository extends IRepository
{
    public function paginateByUserId(int $userId, QueryOption $options): LengthAwarePaginator;

    public function findByUserId(int $userId, int $orderId): ?Order;
}

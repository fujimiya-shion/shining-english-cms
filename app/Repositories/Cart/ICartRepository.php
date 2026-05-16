<?php

namespace App\Repositories\Cart;

use App\Models\Cart;
use App\Repositories\IRepository;
use Illuminate\Support\Collection;

interface ICartRepository extends IRepository
{
    public function findByUserAndCourse(int $userId, int $courseId): ?Cart;

    public function itemsByUserId(int $userId): Collection;

    /**
     * @return array{items: int, quantity: int}
     */
    public function countByUserId(int $userId): array;

    public function clearByUserId(int $userId): void;

    public function addCourse(int $userId, int $courseId, int $quantity = 1): Cart;
}

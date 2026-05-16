<?php

namespace App\Services\Cart;

use App\Models\Cart;
use App\Services\IService;
use Illuminate\Support\Collection;

interface ICartService extends IService
{
    public function addCourse(int $userId, int $courseId, int $quantity = 1): Cart;

    public function itemsByUserId(int $userId): Collection;

    /**
     * @return array{items: int, quantity: int}
     */
    public function countByUserId(int $userId): array;

    public function clearByUserId(int $userId): void;

    public function hasCourse(int $userId, int $courseId): bool;
}

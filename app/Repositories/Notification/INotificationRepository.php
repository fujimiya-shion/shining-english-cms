<?php

namespace App\Repositories\Notification;

use App\Repositories\IRepository;
use Illuminate\Pagination\LengthAwarePaginator;

interface INotificationRepository extends IRepository
{
    public function listByUserId(int $userId, int $perPage = 15, int $page = 1): LengthAwarePaginator;

    public function unreadCount(int $userId): int;

    public function markAsRead(string $notificationId, int $userId): bool;

    public function markAllAsRead(int $userId): int;
}

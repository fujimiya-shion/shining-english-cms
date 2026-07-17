<?php

namespace App\Services\Notification;

use App\Repositories\Notification\INotificationRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class NotificationService implements INotificationService
{
    public function __construct(
        protected INotificationRepository $notificationRepository,
    ) {}

    public function listByUserId(int $userId, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return $this->notificationRepository->listByUserId($userId, $perPage, $page);
    }

    public function unreadCount(int $userId): int
    {
        return $this->notificationRepository->unreadCount($userId);
    }

    public function markAsRead(string $notificationId, int $userId): bool
    {
        return $this->notificationRepository->markAsRead($notificationId, $userId);
    }

    public function markAllAsRead(int $userId): int
    {
        return $this->notificationRepository->markAllAsRead($userId);
    }
}

<?php

namespace App\Repositories\Notification;

use App\Repositories\Repository;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class NotificationRepository extends Repository implements INotificationRepository
{
    public function __construct(DatabaseNotification $model)
    {
        parent::__construct($model);
    }

    public function listByUserId(int $userId, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return $this->model
            ->newQuery()
            ->where('notifiable_id', $userId)
            ->where('notifiable_type', 'App\Models\User')
            ->orderBy('created_at', 'desc')
            ->paginate(perPage: $perPage, page: $page);
    }

    public function unreadCount(int $userId): int
    {
        return $this->model
            ->newQuery()
            ->where('notifiable_id', $userId)
            ->where('notifiable_type', 'App\Models\User')
            ->whereNull('read_at')
            ->count();
    }

    public function markAsRead(string $notificationId, int $userId): bool
    {
        return (bool) DB::table('notifications')
            ->where('id', $notificationId)
            ->where('notifiable_id', $userId)
            ->where('notifiable_type', 'App\Models\User')
            ->update(['read_at' => now()]);
    }

    public function markAllAsRead(int $userId): int
    {
        return DB::table('notifications')
            ->where('notifiable_id', $userId)
            ->where('notifiable_type', 'App\Models\User')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
}

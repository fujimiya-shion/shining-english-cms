<?php

namespace App\Http\Controllers\Api\V1\Notification;

use App\Http\Controllers\Api\ApiController;
use App\Services\Notification\INotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends ApiController
{
    public function __construct(
        protected INotificationService $notificationService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $perPage = (int) ($request->input('per_page', 15));
        $page = (int) ($request->input('page', 1));

        $notifications = $this->notificationService->listByUserId(
            userId: (int) $user->id,
            perPage: max(1, min(50, $perPage)),
            page: max(1, $page),
        );

        $items = $notifications->map(function (DatabaseNotification $notification): array {
            $data = $notification->data;

            return [
                'id' => $notification->id,
                'type' => $data['type'] ?? 'unknown',
                'title' => $data['title'] ?? '',
                'body' => $data['body'] ?? '',
                'data' => $data,
                'read_at' => $notification->read_at,
                'created_at' => $notification->created_at,
            ];
        });

        return $this->success(
            data: $items->values()->all(),
            meta: [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
            ],
        );
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $user = $request->user();

        $count = $this->notificationService->unreadCount((int) $user->id);

        return $this->success(data: [
            'unread_count' => $count,
        ]);
    }

    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        $updated = $this->notificationService->markAsRead($id, (int) $user->id);

        if (! $updated) {
            return $this->notfound('Notification not found.');
        }

        return $this->success(message: 'Đã đánh dấu thông báo là đã đọc.');
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $user = $request->user();

        $count = $this->notificationService->markAllAsRead((int) $user->id);

        return $this->success(
            message: 'Đã đánh dấu tất cả thông báo là đã đọc.',
            data: ['updated_count' => $count],
        );
    }
}

<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class EnrollmentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $courseId,
        public string $courseName,
        public ?string $courseThumbnail = null,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => NotificationType::Enrollment->value,
            'course_id' => $this->courseId,
            'course_name' => $this->courseName,
            'course_thumbnail' => $this->courseThumbnail,
            'title' => 'Ghi danh khóa học',
            'body' => "Bạn đã được ghi danh vào khóa học \"{$this->courseName}\".",
        ];
    }
}

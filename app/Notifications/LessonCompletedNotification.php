<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class LessonCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $courseId,
        public string $courseName,
        public int $lessonId,
        public string $lessonName,
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
            'type' => NotificationType::LessonCompleted->value,
            'course_id' => $this->courseId,
            'course_name' => $this->courseName,
            'lesson_id' => $this->lessonId,
            'lesson_name' => $this->lessonName,
            'title' => 'Hoàn thành bài học',
            'body' => "Bạn đã hoàn thành bài học \"{$this->lessonName}\" trong khóa học \"{$this->courseName}\".",
        ];
    }
}

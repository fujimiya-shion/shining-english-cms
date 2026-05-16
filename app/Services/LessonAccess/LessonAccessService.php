<?php

namespace App\Services\LessonAccess;

use App\Models\Lesson;
use App\Services\Enrollment\IEnrollmentService;

class LessonAccessService implements ILessonAccessService
{
    public function __construct(
        protected IEnrollmentService $enrollmentService,
    ) {}

    public function canWatchLessonVideo(?int $userId, Lesson $lesson): bool
    {
        if ((bool) $lesson->is_preview_free) {
            return true;
        }

        return $this->canAccessLessonProtectedContent($userId, $lesson);
    }

    public function canAccessLessonProtectedContent(?int $userId, Lesson $lesson): bool
    {
        if (! $userId) {
            return false;
        }

        $courseId = (int) ($lesson->course_id ?? 0);
        if ($courseId <= 0) {
            return false;
        }

        return $this->enrollmentService->isEnrolled($userId, $courseId);
    }
}

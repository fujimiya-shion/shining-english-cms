<?php

namespace App\Services\LessonAccess;

use App\Models\Lesson;

interface ILessonAccessService
{
    public function canWatchLessonVideo(?int $userId, Lesson $lesson): bool;

    public function canAccessLessonProtectedContent(?int $userId, Lesson $lesson): bool;
}

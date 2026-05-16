<?php

namespace App\Services\LessonComment;

use App\Models\LessonComment;
use App\Services\IService;

interface ILessonCommentService extends IService
{
    public function createForUser(int $lessonId, int $userId, string $content): LessonComment;
}

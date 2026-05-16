<?php

namespace App\Services\LessonNote;

use App\Models\LessonNote;
use App\Services\IService;
use Illuminate\Database\Eloquent\Collection;

interface ILessonNoteService extends IService
{
    /**
     * @return Collection<int, LessonNote>
     */
    public function listByUserId(int $userId): Collection;

    /**
     * @return Collection<int, LessonNote>
     */
    public function listByLessonId(int $userId, int $lessonId): Collection;

    public function createForUser(int $userId, int $lessonId, string $content): LessonNote;

    public function deleteByUserId(int $userId, int $noteId): bool;
}

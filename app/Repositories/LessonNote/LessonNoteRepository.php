<?php

namespace App\Repositories\LessonNote;

use App\Models\LessonNote;
use App\Repositories\Repository;
use Illuminate\Database\Eloquent\Collection;

class LessonNoteRepository extends Repository implements ILessonNoteRepository
{
    public function __construct(LessonNote $model)
    {
        parent::__construct($model);
    }

    public function listByUserId(int $userId): Collection
    {
        return $this->model->newQuery()
            ->with(['lesson:id,name,course_id', 'lesson.course:id,name,slug'])
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->get();
    }

    public function listByLessonId(int $userId, int $lessonId): Collection
    {
        return $this->model->newQuery()
            ->with(['lesson:id,name,course_id', 'lesson.course:id,name,slug'])
            ->where('user_id', $userId)
            ->where('lesson_id', $lessonId)
            ->orderByDesc('created_at')
            ->get();
    }

    public function findOwnedById(int $userId, int $noteId): ?LessonNote
    {
        $record = $this->model->newQuery()
            ->with(['lesson:id,name,course_id', 'lesson.course:id,name,slug'])
            ->where('user_id', $userId)
            ->where('id', $noteId)
            ->first();

        return $record instanceof LessonNote ? $record : null;
    }
}

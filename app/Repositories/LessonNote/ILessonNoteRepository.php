<?php

namespace App\Repositories\LessonNote;

use App\Models\LessonNote;
use App\Repositories\IRepository;
use Illuminate\Database\Eloquent\Collection;

interface ILessonNoteRepository extends IRepository
{
    /**
     * @return Collection<int, LessonNote>
     */
    public function listByUserId(int $userId): Collection;

    /**
     * @return Collection<int, LessonNote>
     */
    public function listByLessonId(int $userId, int $lessonId): Collection;

    public function findOwnedById(int $userId, int $noteId): ?LessonNote;
}

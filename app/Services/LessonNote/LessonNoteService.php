<?php

namespace App\Services\LessonNote;

use App\Models\LessonNote;
use App\Repositories\LessonNote\ILessonNoteRepository;
use App\Services\Service;
use Illuminate\Database\Eloquent\Collection;

class LessonNoteService extends Service implements ILessonNoteService
{
    public function __construct(
        protected ILessonNoteRepository $lessonNoteRepository,
    ) {
        parent::__construct($lessonNoteRepository);
    }

    public function listByUserId(int $userId): Collection
    {
        return $this->lessonNoteRepository->listByUserId($userId);
    }

    public function listByLessonId(int $userId, int $lessonId): Collection
    {
        return $this->lessonNoteRepository->listByLessonId($userId, $lessonId);
    }

    public function createForUser(int $userId, int $lessonId, string $content): LessonNote
    {
        /** @var LessonNote $note */
        $note = $this->lessonNoteRepository->create([
            'lesson_id' => $lessonId,
            'user_id' => $userId,
            'content' => trim($content),
        ]);

        return $note->load(['lesson:id,name,course_id', 'lesson.course:id,name,slug']);
    }

    public function deleteByUserId(int $userId, int $noteId): bool
    {
        $note = $this->lessonNoteRepository->findOwnedById($userId, $noteId);
        if (! $note) {
            return false;
        }

        return $this->lessonNoteRepository->delete($note->id);
    }
}

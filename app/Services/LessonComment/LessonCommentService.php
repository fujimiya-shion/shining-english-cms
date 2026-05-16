<?php

namespace App\Services\LessonComment;

use App\Models\LessonComment;
use App\Repositories\LessonComment\ILessonCommentRepository;
use App\Services\Service;

class LessonCommentService extends Service implements ILessonCommentService
{
    public function __construct(
        protected ILessonCommentRepository $lessonCommentRepository,
    ) {
        parent::__construct($lessonCommentRepository);
    }

    public function createForUser(int $lessonId, int $userId, string $content): LessonComment
    {
        /** @var LessonComment $comment */
        $comment = $this->lessonCommentRepository->create([
            'lesson_id' => $lessonId,
            'user_id' => $userId,
            'content' => trim($content),
        ]);

        return $comment->load('user:id,name,avatar');
    }
}

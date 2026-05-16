<?php

namespace App\Http\Controllers\Api\V1\Lesson;

use App\Http\Controllers\Api\ApiController;
use App\Services\Lesson\ILessonService;
use App\Services\LessonNote\ILessonNoteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LessonNoteController extends ApiController
{
    public function __construct(
        protected ILessonNoteService $lessonNoteService,
        protected ILessonService $lessonService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        return $this->success(
            message: 'Get lesson notes successfully',
            data: $this->lessonNoteService->listByUserId($user->id),
        );
    }

    public function indexByLesson(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $lesson = $this->lessonService->getById($id);

        if (! $lesson) {
            return $this->notfound('Lesson not found');
        }

        return $this->success(
            message: 'Get lesson notes successfully',
            data: $this->lessonNoteService->listByLessonId($user->id, $id),
        );
    }

    public function store(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $lesson = $this->lessonService->getById($id);

        if (! $lesson) {
            return $this->notfound('Lesson not found');
        }

        $validated = $request->validate([
            'content' => ['required', 'string'],
        ], [
            'content.required' => 'Content is required.',
            'content.string' => 'Content must be a string.',
        ]);

        $note = $this->lessonNoteService->createForUser($user->id, $id, $validated['content']);

        return $this->created($note, 'Note created');
    }

    public function delete(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        if (! $this->lessonNoteService->deleteByUserId($user->id, $id)) {
            return $this->notfound('Note not found');
        }

        return $this->deleted('Note deleted');
    }
}

<?php

namespace App\Http\Controllers\Api\V1\Lesson;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\Lesson\LessonCommentStoreRequest;
use App\Services\LessonComment\ILessonCommentService;
use App\Services\IService;
use App\Services\Lesson\ILessonService;
use App\Services\LessonAccess\ILessonAccessService;
use App\Traits\ApiBehaviour;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LessonController extends ApiController
{
    use ApiBehaviour;

    public function __construct(
        protected ILessonService $service,
        protected ILessonAccessService $lessonAccessService,
        protected ILessonCommentService $lessonCommentService,
    ) {}

    protected function service(): IService
    {
        return $this->service;
    }

    public function quiz(Request $request): JsonResponse
    {
        $id = (int) $request->route('id');
        $lesson = $this->service->getById($id);

        if (! $lesson) {
            return $this->notfound();
        }

        $userId = $request->user()?->id;
        if (! $this->lessonAccessService->canAccessLessonProtectedContent($userId, $lesson)) {
            return $this->unauthorized('Lesson access denied');
        }

        $quiz = $lesson->quiz()
            ->with(['questions.answers'])
            ->first();

        if (! $quiz) {
            return $this->notfound();
        }

        return $this->success('Get Quiz Successfully', $quiz);
    }

    public function downloadDocument(Request $request, int $id, int $documentIndex): JsonResponse|StreamedResponse
    {
        $lesson = $this->service->getById($id);

        if (! $lesson) {
            return $this->notfound();
        }

        $userId = $request->user()?->id;
        if (! $this->lessonAccessService->canAccessLessonProtectedContent($userId, $lesson)) {
            return $this->unauthorized('Lesson access denied');
        }

        $paths = collect($lesson->documents ?? [])
            ->filter(fn (mixed $value): bool => is_string($value) && trim($value) !== '')
            ->values();

        $path = $paths->get($documentIndex);
        if (! is_string($path) || ! Storage::disk('local')->exists($path)) {
            return $this->notfound();
        }

        $fileName = collect($lesson->document_names ?? [])->values()->get($documentIndex);
        if (! is_string($fileName) || trim($fileName) === '') {
            $fileName = basename($path);
        }

        return Storage::disk('local')->download($path, $fileName);
    }

    public function video(Request $request, int $id): JsonResponse|BinaryFileResponse
    {
        $lesson = $this->service->getById($id);

        if (! $lesson) {
            return $this->notfound();
        }

        $userId = $request->user()?->id;
        if (! $this->lessonAccessService->canWatchLessonVideo($userId, $lesson)) {
            return $this->unauthorized('Lesson video access denied');
        }

        $path = is_string($lesson->video_url ?? null) ? trim($lesson->video_url) : '';
        if ($path === '' || ! Storage::disk('local')->exists($path)) {
            return $this->notfound();
        }

        $response = new BinaryFileResponse(Storage::disk('local')->path($path));
        $response->headers->set('Accept-Ranges', 'bytes');
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE,
            basename($path),
        );

        return $response;
    }

    public function storeComment(LessonCommentStoreRequest $request, int $id): JsonResponse
    {
        $lesson = $this->service->getById($id);
        if (! $lesson) {
            return $this->notfound();
        }

        $user = $request->user();
        if (! $this->lessonAccessService->canWatchLessonVideo($user->id, $lesson)) {
            return $this->unauthorized('Lesson access denied');
        }

        $validated = $request->validated();
        $comment = $this->lessonCommentService->createForUser(
            lessonId: $id,
            userId: $user->id,
            content: (string) $validated['content'],
        );

        return $this->created($comment, 'Comment submitted');
    }
}

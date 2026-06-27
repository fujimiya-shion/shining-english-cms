<?php

namespace App\Http\Controllers\Api\V1\Course;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\Course\CourseCurrentLessonRequest;
use App\Http\Requests\Api\V1\Course\CourseFilterRequest;
use App\Http\Requests\Api\V1\Course\CourseReviewStoreRequest;
use App\Services\Cart\ICartService;
use App\Services\Course\ICourseService;
use App\Services\CourseReview\ICourseReviewService;
use App\Services\Enrollment\IEnrollmentService;
use App\Services\IService;
use App\Services\Star\IStarService;
use App\Traits\ApiBehaviour;
use App\ValueObjects\CourseFilter;
use App\ValueObjects\MetaPagination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CourseController extends ApiController
{
    use ApiBehaviour;

    public function __construct(
        protected ICourseService $service,
        protected ICartService $cartService,
        protected IEnrollmentService $enrollmentService,
        protected ICourseReviewService $courseReviewService,
        protected IStarService $starService,
    ) {}

    protected function service(): IService
    {
        return $this->service;
    }

    public function filter(CourseFilterRequest $request): JsonResponse
    {
        $filters = CourseFilter::fromArray($request->validated());
        $paginator = $this->service->filter($filters);
        $collections = $paginator->getCollection();
        $meta = MetaPagination::fromLengthAwarePaginator($paginator);

        return $this->success(
            data: $collections,
            meta: $meta->toArray(),
        );
    }

    public function getFilterProps(): JsonResponse
    {
        return $this->success(data: $this->service->getFilterProps());
    }

    public function showBySlug(string $slug): JsonResponse
    {
        $record = $this->service->getBySlug($slug);

        if (! $record) {
            return $this->notfound();
        }

        return $this->success(data: $record);
    }

    public function access(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $course = $this->service->getById($id);

        if (! $course) {
            return $this->notfound();
        }

        $isEnrolled = $this->enrollmentService->isEnrolled($user->id, $id);
        $isFreeCourse = (int) ($course->price ?? 0) === 0;
        $starBalance = $this->starService->getBalance((int) $user->id);

        return $this->success(data: [
            'course_id' => $id,
            'enrolled' => $isEnrolled,
            'pending_access' => $this->enrollmentService->hasPendingEnrollment($user->id, $id),
            'in_cart' => $this->cartService->hasCourse($user->id, $id),
            'is_free_course' => $isFreeCourse,
            'can_enroll_free' => $isFreeCourse && ! $isEnrolled,
            'allow_star_payment' => (bool) ($course->allow_star_payment ?? false),
            'star_price' => (int) ($course->star_price ?? 0),
            'star_balance' => $starBalance,
        ]);
    }

    public function learningProgress(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $course = $this->service->getById($id);
        if (! $course) {
            return $this->notfound();
        }

        if (! $this->enrollmentService->isEnrolled($user->id, $id)) {
            return $this->unauthorized('Course access denied');
        }

        $progress = $this->enrollmentService->getLearningProgress($user->id, $id);
        if (! $progress) {
            return $this->notfound();
        }

        return $this->success(data: $progress);
    }

    public function completeLesson(Request $request, int $id, int $lessonId): JsonResponse
    {
        $user = $request->user();

        $course = $this->service->getById($id);
        if (! $course) {
            return $this->notfound();
        }

        if (! $this->enrollmentService->isEnrolled($user->id, $id)) {
            return $this->unauthorized('Course access denied');
        }

        $progress = $this->enrollmentService->completeLesson($user->id, $id, $lessonId);
        if (! $progress) {
            return $this->notfound();
        }

        return $this->success(data: $progress);
    }

    public function setCurrentLesson(CourseCurrentLessonRequest $request, int $id): JsonResponse
    {
        $user = $request->user();
        $lessonId = (int) $request->validated('lesson_id');

        $course = $this->service->getById($id);
        if (! $course) {
            return $this->notfound();
        }

        if (! $this->enrollmentService->isEnrolled($user->id, $id)) {
            return $this->unauthorized('Course access denied');
        }

        $progress = $this->enrollmentService->setCurrentLesson($user->id, $id, $lessonId);
        if (! $progress) {
            return $this->notfound();
        }

        return $this->success(data: $progress);
    }

    public function storeReview(CourseReviewStoreRequest $request, int $id): JsonResponse
    {
        $user = $request->user();
        $course = $this->service->getById($id);

        if (! $course) {
            return $this->notfound();
        }

        if (! $this->enrollmentService->isEnrolled($user->id, $id)) {
            return $this->unauthorized('Course access denied');
        }

        $validated = $request->validated();

        $review = $this->courseReviewService->upsertByUser(
            courseId: $id,
            userId: $user->id,
            rating: (int) $validated['rating'],
            content: (string) $validated['content'],
        );

        return $this->created($review, 'Review submitted');
    }
}

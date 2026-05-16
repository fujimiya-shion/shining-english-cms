<?php

namespace App\Services\Enrollment;

use App\Enums\OrderStatus;
use App\Models\Enrollment;
use App\Models\CourseReview;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Repositories\Enrollment\IEnrollmentRepository;
use App\Services\Service;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EnrollmentService extends Service implements IEnrollmentService
{
    protected IEnrollmentRepository $enrollmentRepository;

    public function __construct(IEnrollmentRepository $repository)
    {
        parent::__construct($repository);
        $this->enrollmentRepository = $repository;
    }

    public function enroll(int $userId, int $courseId, ?int $orderId = null): Enrollment
    {
        $existing = $this->enrollmentRepository->findByUserAndCourseWithTrashed($userId, $courseId);

        if ($existing) {
            if ($existing->trashed()) {
                $existing->restore();
                $existing->fill([
                    'order_id' => $orderId,
                    'enrolled_at' => now(),
                ]);
                $existing->save();

                return $existing->refresh();
            }

            return $existing;
        }

        return DB::transaction(function () use ($userId, $courseId, $orderId): Enrollment {
            try {
                return $this->enrollmentRepository->create([
                    'user_id' => $userId,
                    'course_id' => $courseId,
                    'order_id' => $orderId,
                    'enrolled_at' => now(),
                ]);
            } catch (QueryException $exception) {
                $existing = $this->enrollmentRepository->findByUserAndCourse($userId, $courseId);

                if ($existing) {
                    return $existing;
                }

                throw $exception;
            }
        });
    }

    public function isEnrolled(int $userId, int $courseId): bool
    {
        $enrollment = $this->enrollmentRepository->findByUserAndCourse($userId, $courseId);

        if (! $enrollment) {
            return false;
        }

        if (! $enrollment->order_id) {
            return true;
        }

        return $enrollment->order?->status === OrderStatus::Paid;
    }

    public function hasPendingEnrollment(int $userId, int $courseId): bool
    {
        $enrollment = $this->enrollmentRepository->findByUserAndCourse($userId, $courseId);

        if (! $enrollment || ! $enrollment->order_id) {
            return false;
        }

        return $enrollment->order?->status === OrderStatus::Pending;
    }

    public function getLearningProgress(int $userId, int $courseId): ?array
    {
        $enrollment = $this->enrollmentRepository->findByUserAndCourse($userId, $courseId);
        if (! $enrollment) {
            return null;
        }

        $orderedLessons = $this->getOrderedLessons($courseId);
        $orderedLessonIds = $orderedLessons->pluck('id')->map(fn (mixed $id): int => (int) $id)->all();
        [$completedLessonIds, $currentLessonId] = $this->collectProgressState($userId, $courseId, $orderedLessonIds);

        return $this->buildProgressPayload(
            userId: $userId,
            courseId: $courseId,
            currentLessonId: $currentLessonId,
            completedLessonIds: $completedLessonIds,
            totalLessons: count($orderedLessonIds),
        );
    }

    public function completeLesson(int $userId, int $courseId, int $lessonId): ?array
    {
        $enrollment = $this->enrollmentRepository->findByUserAndCourse($userId, $courseId);
        if (! $enrollment) {
            return null;
        }

        $orderedLessons = $this->getOrderedLessons($courseId);
        $orderedLessonIds = $orderedLessons->pluck('id')->map(fn (mixed $id): int => (int) $id)->values()->all();
        if (! in_array($lessonId, $orderedLessonIds, true)) {
            return null;
        }

        DB::transaction(function () use ($userId, $courseId, $lessonId): void {
            LessonProgress::query()
                ->where('user_id', $userId)
                ->where('course_id', $courseId)
                ->update(['is_current' => false]);

            LessonProgress::query()->updateOrCreate(
                [
                    'user_id' => $userId,
                    'course_id' => $courseId,
                    'lesson_id' => $lessonId,
                ],
                [
                    'completed_at' => now(),
                    'is_current' => false,
                ],
            );
        });

        [$completedLessonIds, $currentLessonId] = $this->collectProgressState($userId, $courseId, $orderedLessonIds);
        $nextLesson = null;

        if ($currentLessonId !== null) {
            LessonProgress::query()->updateOrCreate(
                [
                    'user_id' => $userId,
                    'course_id' => $courseId,
                    'lesson_id' => $currentLessonId,
                ],
                [
                    'is_current' => true,
                ],
            );

            $nextLesson = $orderedLessons->first(
                fn (Lesson $lesson): bool => (int) $lesson->id === $currentLessonId,
            );
        }

        return [
            ...$this->buildProgressPayload(
                userId: $userId,
                courseId: $courseId,
                currentLessonId: $currentLessonId,
                completedLessonIds: $completedLessonIds,
                totalLessons: count($orderedLessonIds),
            ),
            'next_lesson' => $nextLesson
                ? [
                    'id' => (int) $nextLesson->id,
                    'has_quiz' => (bool) $nextLesson->has_quiz,
                ]
                : null,
        ];
    }

    public function setCurrentLesson(int $userId, int $courseId, int $lessonId): ?array
    {
        $enrollment = $this->enrollmentRepository->findByUserAndCourse($userId, $courseId);
        if (! $enrollment) {
            return null;
        }

        $orderedLessons = $this->getOrderedLessons($courseId);
        $orderedLessonIds = $orderedLessons->pluck('id')->map(fn (mixed $id): int => (int) $id)->all();
        if (! in_array($lessonId, $orderedLessonIds, true)) {
            return null;
        }

        DB::transaction(function () use ($userId, $courseId, $lessonId): void {
            LessonProgress::query()
                ->where('user_id', $userId)
                ->where('course_id', $courseId)
                ->update(['is_current' => false]);

            LessonProgress::query()->updateOrCreate(
                [
                    'user_id' => $userId,
                    'course_id' => $courseId,
                    'lesson_id' => $lessonId,
                ],
                [
                    'is_current' => true,
                ],
            );
        });

        [$completedLessonIds, $currentLessonId] = $this->collectProgressState($userId, $courseId, $orderedLessonIds);

        return $this->buildProgressPayload(
            userId: $userId,
            courseId: $courseId,
            currentLessonId: $currentLessonId,
            completedLessonIds: $completedLessonIds,
            totalLessons: count($orderedLessonIds),
        );
    }

    /**
     * @return Collection<int, Lesson>
     */
    private function getOrderedLessons(int $courseId): Collection
    {
        return Lesson::query()
            ->where('course_id', $courseId)
            ->orderBy('group_order')
            ->orderBy('lesson_order')
            ->orderBy('id')
            ->get(['id', 'has_quiz']);
    }

    /**
     * @param  list<int>  $orderedLessonIds
     * @return array{0:list<int>,1:int|null}
     */
    private function collectProgressState(int $userId, int $courseId, array $orderedLessonIds): array
    {
        if ($orderedLessonIds === []) {
            return [[], null];
        }

        $progressRows = LessonProgress::query()
            ->where('user_id', $userId)
            ->where('course_id', $courseId)
            ->get(['lesson_id', 'is_current', 'completed_at', 'updated_at']);

        $allowedSet = array_fill_keys($orderedLessonIds, true);
        $completedSet = [];
        $currentCandidate = null;
        $currentCandidateUpdatedAt = null;

        foreach ($progressRows as $row) {
            $lessonId = (int) $row->lesson_id;
            if (! isset($allowedSet[$lessonId])) {
                continue;
            }

            if ($row->completed_at !== null) {
                $completedSet[$lessonId] = true;
            }

            if ($row->is_current) {
                $updatedAt = $row->updated_at?->getTimestamp() ?? 0;
                if ($currentCandidateUpdatedAt === null || $updatedAt >= $currentCandidateUpdatedAt) {
                    $currentCandidate = $lessonId;
                    $currentCandidateUpdatedAt = $updatedAt;
                }
            }
        }

        $completedLessonIds = [];
        foreach ($orderedLessonIds as $lessonId) {
            if (isset($completedSet[$lessonId])) {
                $completedLessonIds[] = $lessonId;
            }
        }

        $currentLessonId = $currentCandidate;
        foreach ($orderedLessonIds as $lessonId) {
            if (! isset($completedSet[$lessonId])) {
                if ($currentLessonId === null || ! in_array($currentLessonId, $orderedLessonIds, true)) {
                    $currentLessonId = $lessonId;
                }
                break;
            }
        }

        return [$completedLessonIds, $currentLessonId];
    }

    /**
     * @param  list<int>  $completedLessonIds
     * @return array{
     *   course_id:int,
     *   current_lesson_id:int|null,
     *   completed_lesson_ids:list<int>,
     *   total_lessons:int,
     *   progress_percentage:float,
     *   has_reviewed:bool
     * }
     */
    private function buildProgressPayload(
        int $userId,
        int $courseId,
        ?int $currentLessonId,
        array $completedLessonIds,
        int $totalLessons
    ): array
    {
        $progressPercentage = $totalLessons > 0
            ? round((count($completedLessonIds) / $totalLessons) * 100, 2)
            : 0.0;
        $hasReviewed = CourseReview::query()
            ->where('course_id', $courseId)
            ->where('user_id', $userId)
            ->exists();

        return [
            'course_id' => $courseId,
            'current_lesson_id' => $currentLessonId,
            'completed_lesson_ids' => $completedLessonIds,
            'total_lessons' => $totalLessons,
            'progress_percentage' => $progressPercentage,
            'has_reviewed' => $hasReviewed,
        ];
    }
}

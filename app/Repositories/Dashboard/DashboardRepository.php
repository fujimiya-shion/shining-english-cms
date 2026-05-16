<?php

namespace App\Repositories\Dashboard;

use App\Models\Enrollment;
use App\Models\LessonProgress;
use App\Models\UserQuizAttempt;
use App\Repositories\Repository;
use Illuminate\Support\Collection;

class DashboardRepository extends Repository implements IDashboardRepository
{
    public function __construct(Enrollment $model)
    {
        parent::__construct($model);
    }

    public function getEnrollmentsByUserId(int $userId): Collection
    {
        return $this->model
            ->newQuery()
            ->where('user_id', $userId)
            ->with([
                'course' => function ($query) {
                    $query
                        ->select(['id', 'name', 'slug', 'thumbnail', 'price', 'category_id', 'learned'])
                        ->withCardMetrics()
                        ->with(['category:id,name,slug']);
                },
            ])
            ->latest('enrolled_at')
            ->get();
    }

    public function getLessonProgressByUserAndCourseIds(int $userId, Collection $courseIds): Collection
    {
        if ($courseIds->isEmpty()) {
            return collect();
        }

        return LessonProgress::query()
            ->where('user_id', $userId)
            ->whereIn('course_id', $courseIds)
            ->with(['lesson:id,name,course_id,duration_minutes', 'lesson.course:id,name'])
            ->get();
    }

    public function getRecentQuizAttemptsByUserId(int $userId, int $limit = 10): Collection
    {
        return UserQuizAttempt::query()
            ->where('user_id', $userId)
            ->with(['quiz:id,lesson_id', 'quiz.lesson:id,name,course_id', 'quiz.lesson.course:id,name'])
            ->latest('submitted_at')
            ->limit($limit)
            ->get();
    }
}

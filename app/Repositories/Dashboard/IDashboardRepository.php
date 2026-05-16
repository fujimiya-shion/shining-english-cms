<?php

namespace App\Repositories\Dashboard;

use App\Models\Enrollment;
use App\Models\LessonProgress;
use App\Models\UserQuizAttempt;
use App\Repositories\IRepository;
use Illuminate\Support\Collection;

interface IDashboardRepository extends IRepository
{
    /**
     * @return Collection<int, Enrollment>
     */
    public function getEnrollmentsByUserId(int $userId): Collection;

    /**
     * @param  Collection<int, int>  $courseIds
     * @return Collection<int, LessonProgress>
     */
    public function getLessonProgressByUserAndCourseIds(int $userId, Collection $courseIds): Collection;

    /**
     * @return Collection<int, UserQuizAttempt>
     */
    public function getRecentQuizAttemptsByUserId(int $userId, int $limit = 10): Collection;
}

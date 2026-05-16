<?php

namespace App\Repositories\CourseReview;

use App\Models\CourseReview;
use App\Repositories\Repository;

class CourseReviewRepository extends Repository implements ICourseReviewRepository
{
    public function __construct(CourseReview $model)
    {
        parent::__construct($model);
    }

    public function findByCourseAndUser(int $courseId, int $userId): ?CourseReview
    {
        $record = $this->model->newQuery()
            ->where('course_id', $courseId)
            ->where('user_id', $userId)
            ->first();

        return $record instanceof CourseReview ? $record : null;
    }

    public function averageRatingByCourse(int $courseId): ?float
    {
        $avg = $this->model->newQuery()
            ->where('course_id', $courseId)
            ->avg('rating');

        if ($avg === null) {
            return null;
        }

        return (float) $avg;
    }
}

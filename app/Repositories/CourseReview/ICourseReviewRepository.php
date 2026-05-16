<?php

namespace App\Repositories\CourseReview;

use App\Models\CourseReview;
use App\Repositories\IRepository;

interface ICourseReviewRepository extends IRepository
{
    public function findByCourseAndUser(int $courseId, int $userId): ?CourseReview;

    public function averageRatingByCourse(int $courseId): ?float;
}

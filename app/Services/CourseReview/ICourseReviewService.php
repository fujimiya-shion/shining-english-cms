<?php

namespace App\Services\CourseReview;

use App\Models\CourseReview;
use App\Services\IService;

interface ICourseReviewService extends IService
{
    public function upsertByUser(
        int $courseId,
        int $userId,
        int $rating,
        string $content,
    ): CourseReview;
}

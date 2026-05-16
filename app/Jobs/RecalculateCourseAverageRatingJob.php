<?php

namespace App\Jobs;

use App\Repositories\Course\ICourseRepository;
use App\Repositories\CourseReview\ICourseReviewRepository;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RecalculateCourseAverageRatingJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $courseId,
    ) {}

    public function handle(
        ICourseReviewRepository $courseReviewRepository,
        ICourseRepository $courseRepository,
    ): void {
        $avgRating = $courseReviewRepository->averageRatingByCourse($this->courseId);

        $courseRepository->update($this->courseId, [
            'rating' => $avgRating !== null ? round($avgRating, 1) : null,
        ]);
    }
}

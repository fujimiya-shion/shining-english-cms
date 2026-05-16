<?php

namespace App\Services\CourseReview;

use App\Jobs\RecalculateCourseAverageRatingJob;
use App\Models\CourseReview;
use App\Repositories\CourseReview\ICourseReviewRepository;
use App\Services\Service;

class CourseReviewService extends Service implements ICourseReviewService
{
    public function __construct(
        protected ICourseReviewRepository $courseReviewRepository,
    ) {
        parent::__construct($courseReviewRepository);
    }

    public function upsertByUser(
        int $courseId,
        int $userId,
        int $rating,
        string $content,
    ): CourseReview {
        $existing = $this->courseReviewRepository->findByCourseAndUser($courseId, $userId);

        if ($existing) {
            /** @var CourseReview $review */
            $review = $this->courseReviewRepository->update($existing->id, [
                'rating' => $rating,
                'content' => trim($content),
            ]);
        } else {
            /** @var CourseReview $review */
            $review = $this->courseReviewRepository->create([
                'course_id' => $courseId,
                'user_id' => $userId,
                'rating' => $rating,
                'content' => trim($content),
            ]);
        }

        dispatch(new RecalculateCourseAverageRatingJob($courseId));

        return $review->load('user:id,name,avatar');
    }
}

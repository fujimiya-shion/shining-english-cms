<?php

namespace App\Services\CourseReview;

use App\Enums\StarTransactionType;
use App\Jobs\RecalculateCourseAverageRatingJob;
use App\Models\CourseReview;
use App\Repositories\CourseReview\ICourseReviewRepository;
use App\Services\Service;
use App\Services\Star\IStarService;

class CourseReviewService extends Service implements ICourseReviewService
{
    public function __construct(
        protected ICourseReviewRepository $courseReviewRepository,
        protected IStarService $starService,
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

        $isNew = ! $existing;

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

        if ($isNew) {
            $this->grantReviewStarReward($userId, $content);
        }

        return $review->load('user:id,name,avatar');
    }

    private function grantReviewStarReward(int $userId, string $content): void
    {
        $contentLength = mb_strlen(trim($content));

        if ($contentLength > 20) {
            $amount = (int) config('const.star.review_full_content', 4);
        } else {
            $amount = (int) config('const.star.review_rating_only', 1);
        }

        if ($amount <= 0) {
            return;
        }

        $this->starService->addStarByUserId(
            $amount,
            $userId,
            __('Thưởng sao khi đánh giá khóa học'),
            StarTransactionType::ReviewReward,
        );
    }
}

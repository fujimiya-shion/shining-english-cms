<?php

use App\Enums\StarTransactionType;
use App\Jobs\RecalculateCourseAverageRatingJob;
use App\Models\Course;
use App\Models\CourseReview;
use App\Models\User;
use App\Repositories\CourseReview\ICourseReviewRepository;
use App\Services\CourseReview\CourseReviewService;
use App\Services\CourseReview\ICourseReviewService;
use App\Services\Star\IStarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

uses(TestCase::class);
uses(RefreshDatabase::class);

it('implements service contract', function (): void {
    $repository = Mockery::mock(ICourseReviewRepository::class);
    $starService = Mockery::mock(IStarService::class);
    $service = new CourseReviewService($repository, $starService);

    assertServiceContract($service, ICourseReviewService::class, $repository);
});

it('creates review and awards stars for content > 20 chars', function (): void {
    Bus::fake();
    config(['const.star.review_full_content' => 4]);

    $user = User::factory()->create();
    $course = Course::factory()->create();

    $starService = Mockery::mock(IStarService::class);
    $starService->shouldReceive('addStarByUserId')
        ->once()
        ->with(4, $user->id, Mockery::type('string'), StarTransactionType::ReviewReward)
        ->andReturnTrue();

    $repository = Mockery::mock(ICourseReviewRepository::class);
    $repository->shouldReceive('findByCourseAndUser')
        ->once()
        ->with($course->id, $user->id)
        ->andReturnNull();
    $repository->shouldReceive('create')
        ->once()
        ->andReturnUsing(function (array $data) use ($user, $course): CourseReview {
            $review = new CourseReview($data);
            $review->id = 1;
            $review->setRelation('user', $user);
            $review->setRelation('course', $course);

            return $review;
        });

    $service = new CourseReviewService($repository, $starService);
    $review = $service->upsertByUser($course->id, $user->id, 5, 'This is a very detailed review with more than twenty characters');

    expect($review)->not->toBeNull();

    Bus::assertDispatched(RecalculateCourseAverageRatingJob::class);
});

it('creates review and awards 1 star for rating-only content', function (): void {
    Bus::fake();
    config(['const.star.review_rating_only' => 1]);

    $user = User::factory()->create();
    $course = Course::factory()->create();

    $starService = Mockery::mock(IStarService::class);
    $starService->shouldReceive('addStarByUserId')
        ->once()
        ->with(1, $user->id, Mockery::type('string'), StarTransactionType::ReviewReward)
        ->andReturnTrue();

    $repository = Mockery::mock(ICourseReviewRepository::class);
    $repository->shouldReceive('findByCourseAndUser')
        ->once()
        ->with($course->id, $user->id)
        ->andReturnNull();
    $repository->shouldReceive('create')
        ->once()
        ->andReturnUsing(function (array $data) use ($user, $course): CourseReview {
            $review = new CourseReview($data);
            $review->id = 2;
            $review->setRelation('user', $user);
            $review->setRelation('course', $course);

            return $review;
        });

    $service = new CourseReviewService($repository, $starService);
    $review = $service->upsertByUser($course->id, $user->id, 4, 'OK');

    expect($review)->not->toBeNull();
});

it('does not award stars when updating existing review', function (): void {
    Bus::fake();

    $user = User::factory()->create();
    $course = Course::factory()->create();
    $existing = new CourseReview;
    $existing->id = 1;

    $starService = Mockery::mock(IStarService::class);
    $starService->shouldReceive('addStarByUserId')->never();

    $repository = Mockery::mock(ICourseReviewRepository::class);
    $repository->shouldReceive('findByCourseAndUser')
        ->once()
        ->with($course->id, $user->id)
        ->andReturn($existing);
    $repository->shouldReceive('update')
        ->once()
        ->andReturnUsing(function (int $id, array $data) use ($user, $course): CourseReview {
            $review = new CourseReview($data);
            $review->id = $id;
            $review->setRelation('user', $user);
            $review->setRelation('course', $course);

            return $review;
        });

    $service = new CourseReviewService($repository, $starService);
    $review = $service->upsertByUser($course->id, $user->id, 3, 'Updated review');

    expect($review)->not->toBeNull();
});

it('skips star reward when config amount is zero for short content', function (): void {
    Bus::fake();
    config(['const.star.review_rating_only' => 0]);

    $user = User::factory()->create();
    $course = Course::factory()->create();

    $starService = Mockery::mock(IStarService::class);
    $starService->shouldReceive('addStarByUserId')->never();

    $repository = Mockery::mock(ICourseReviewRepository::class);
    $repository->shouldReceive('findByCourseAndUser')
        ->once()
        ->with($course->id, $user->id)
        ->andReturnNull();
    $repository->shouldReceive('create')
        ->once()
        ->andReturnUsing(function (array $data) use ($user, $course): CourseReview {
            $review = new CourseReview($data);
            $review->id = 3;
            $review->setRelation('user', $user);
            $review->setRelation('course', $course);

            return $review;
        });

    $service = new CourseReviewService($repository, $starService);
    $review = $service->upsertByUser($course->id, $user->id, 5, 'Hi');

    expect($review)->not->toBeNull();
});

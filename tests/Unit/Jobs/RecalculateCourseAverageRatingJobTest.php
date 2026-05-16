<?php

use App\Jobs\RecalculateCourseAverageRatingJob;
use App\Models\Course;
use App\Models\CourseReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class);
uses(RefreshDatabase::class);

it('recalculates and stores rounded average rating for a course', function (): void {
    $course = Course::factory()->create(['rating' => null]);
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    CourseReview::query()->create([
        'course_id' => $course->id,
        'user_id' => $userA->id,
        'rating' => 4,
        'content' => 'Great',
    ]);
    CourseReview::query()->create([
        'course_id' => $course->id,
        'user_id' => $userB->id,
        'rating' => 5,
        'content' => 'Excellent',
    ]);

    $job = new RecalculateCourseAverageRatingJob($course->id);
    $job->handle(app(\App\Repositories\CourseReview\ICourseReviewRepository::class), app(\App\Repositories\Course\ICourseRepository::class));

    expect((float) $course->fresh()->rating)->toBe(4.5);
});

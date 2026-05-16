<?php

use App\Models\Course;
use App\Models\CourseReview;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('removes duplicate course reviews before adding the unique index', function (): void {
    $migration = require database_path('migrations/2026_05_16_150000_add_unique_index_to_course_reviews_course_user.php');

    $migration->down();

    $course = Course::factory()->create();
    $user = User::factory()->create();

    CourseReview::query()->create([
        'course_id' => $course->id,
        'user_id' => $user->id,
        'rating' => 3,
        'content' => 'Older review',
    ]);

    $latestReview = CourseReview::query()->create([
        'course_id' => $course->id,
        'user_id' => $user->id,
        'rating' => 5,
        'content' => 'Latest review',
    ]);

    expect(CourseReview::withTrashed()->where('course_id', $course->id)->where('user_id', $user->id)->count())->toBe(2);

    $migration->up();

    $remainingReviews = CourseReview::withTrashed()
        ->where('course_id', $course->id)
        ->where('user_id', $user->id)
        ->get();

    expect($remainingReviews)->toHaveCount(1)
        ->and($remainingReviews->first()->id)->toBe($latestReview->id)
        ->and($remainingReviews->first()->content)->toBe('Latest review');

    expect(fn () => CourseReview::query()->create([
        'course_id' => $course->id,
        'user_id' => $user->id,
        'rating' => 4,
        'content' => 'Another review',
    ]))->toThrow(QueryException::class);
});

<?php

use App\Models\Course;
use App\Models\CourseReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('prints info when no courses have reviews', function (): void {
    Course::factory()->create(['rating' => 4.5]);

    $this->artisan('app:recalculate-course-average-star')
        ->expectsOutput('No courses with reviews found.')
        ->assertExitCode(0);
});

it('recalculates average star rating for courses with reviews', function (): void {
    $course = Course::factory()->create(['rating' => 4.5]);
    $user = User::factory()->create();

    CourseReview::factory()->create([
        'course_id' => $course->id,
        'user_id' => $user->id,
        'rating' => 3,
    ]);

    CourseReview::factory()->create([
        'course_id' => $course->id,
        'user_id' => User::factory()->create()->id,
        'rating' => 5,
    ]);

    $this->artisan('app:recalculate-course-average-star')
        ->expectsOutput('Recalculated average star rating for 1 course(s).')
        ->assertExitCode(0);

    $course->refresh();

    expect($course->rating)->toBe(4.0);
});

it('handles multiple courses', function (): void {
    $user = User::factory()->create();

    $courseA = Course::factory()->create(['rating' => 1.0]);
    $courseB = Course::factory()->create(['rating' => 5.0]);

    CourseReview::factory()->create([
        'course_id' => $courseA->id,
        'user_id' => $user->id,
        'rating' => 2,
    ]);

    CourseReview::factory()->create([
        'course_id' => $courseB->id,
        'user_id' => $user->id,
        'rating' => 4,
    ]);

    CourseReview::factory()->create([
        'course_id' => $courseB->id,
        'user_id' => User::factory()->create()->id,
        'rating' => 5,
    ]);

    $this->artisan('app:recalculate-course-average-star')
        ->expectsOutput('Recalculated average star rating for 2 course(s).')
        ->assertExitCode(0);

    expect($courseA->fresh()->rating)->toBe(2.0);
    expect($courseB->fresh()->rating)->toBe(4.5);
});

it('ignores courses without any reviews', function (): void {
    $user = User::factory()->create();

    $courseWithReview = Course::factory()->create(['rating' => 3.0]);
    Course::factory()->create(['rating' => 4.0]);

    CourseReview::factory()->create([
        'course_id' => $courseWithReview->id,
        'user_id' => $user->id,
        'rating' => 5,
    ]);

    $this->artisan('app:recalculate-course-average-star')
        ->assertExitCode(0);

    expect($courseWithReview->fresh()->rating)->toBe(5.0);
});

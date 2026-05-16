<?php

use App\Jobs\GrantLessonStarRewardJob;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonStarReward;
use App\Models\User;
use App\Services\Star\IStarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class);
uses(RefreshDatabase::class);

it('grants lesson video stars only once for the same user and lesson', function (): void {
    $user = User::factory()->create();
    $course = Course::factory()->create();
    $lesson = Lesson::factory()->create([
        'course_id' => $course->id,
        'star_reward_video' => 3,
        'star_reward_quiz' => 0,
    ]);

    $starService = Mockery::mock(IStarService::class);
    $starService->shouldReceive('addStarByUserId')
        ->once()
        ->with(3, $user->id, Mockery::type('string'))
        ->andReturnTrue();
    app()->instance(IStarService::class, $starService);

    $job = new GrantLessonStarRewardJob(
        userId: $user->id,
        courseId: $course->id,
        lessonId: $lesson->id,
        source: GrantLessonStarRewardJob::SOURCE_VIDEO,
    );

    $job->handle($starService);
    $job->handle($starService);

    expect(LessonStarReward::query()->count())->toBe(1);
    expect(LessonStarReward::query()->first()?->amount)->toBe(3);
});

it('skips granting reward when lesson config is zero', function (): void {
    $user = User::factory()->create();
    $course = Course::factory()->create();
    $lesson = Lesson::factory()->create([
        'course_id' => $course->id,
        'star_reward_video' => 0,
        'star_reward_quiz' => 0,
    ]);

    $starService = Mockery::mock(IStarService::class);
    $starService->shouldReceive('addStarByUserId')->never();
    app()->instance(IStarService::class, $starService);

    $job = new GrantLessonStarRewardJob(
        userId: $user->id,
        courseId: $course->id,
        lessonId: $lesson->id,
        source: GrantLessonStarRewardJob::SOURCE_VIDEO,
    );

    $job->handle($starService);

    expect(LessonStarReward::query()->count())->toBe(0);
});

it('grants quiz stars with the quiz reward config', function (): void {
    $user = User::factory()->create();
    $course = Course::factory()->create();
    $lesson = Lesson::factory()->create([
        'course_id' => $course->id,
        'star_reward_video' => 0,
        'star_reward_quiz' => 5,
    ]);

    $starService = Mockery::mock(IStarService::class);
    $starService->shouldReceive('addStarByUserId')
        ->once()
        ->with(5, $user->id, Mockery::type('string'))
        ->andReturnTrue();
    app()->instance(IStarService::class, $starService);

    $job = new GrantLessonStarRewardJob(
        userId: $user->id,
        courseId: $course->id,
        lessonId: $lesson->id,
        source: GrantLessonStarRewardJob::SOURCE_QUIZ,
    );

    $job->handle($starService);

    expect(LessonStarReward::query()->count())->toBe(1);
    expect(LessonStarReward::query()->first()?->source)->toBe(GrantLessonStarRewardJob::SOURCE_QUIZ);
});

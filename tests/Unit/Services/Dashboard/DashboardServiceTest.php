<?php

use App\DTO\Dashboard\DashboardOverviewResponse;
use App\Repositories\Dashboard\IDashboardRepository;
use App\Services\Dashboard\DashboardService;
use App\Services\Dashboard\IDashboardService;
use Illuminate\Support\Collection;
use Tests\TestCase;

uses(TestCase::class);

it('implements service contract', function (): void {
    $repository = Mockery::mock(IDashboardRepository::class);
    $service = new DashboardService($repository);

    expect($service)->toBeInstanceOf(IDashboardService::class);
});

it('returns overview for user with no activity', function (): void {
    $enrollments = new Collection;
    $courseIds = $enrollments->pluck('course_id')->filter()->values();
    $empty = new Collection;

    $repository = Mockery::mock(IDashboardRepository::class);
    $repository->shouldReceive('getEnrollmentsByUserId')
        ->once()
        ->with(1)
        ->andReturn($enrollments);
    $repository->shouldReceive('getLessonProgressByUserAndCourseIds')
        ->once()
        ->with(1, $courseIds)
        ->andReturn($empty);
    $repository->shouldReceive('getRecentQuizAttemptsByUserId')
        ->once()
        ->with(1, 10)
        ->andReturn($empty);

    $service = new DashboardService($repository);
    $result = $service->overview(1);

    expect($result)->toBeInstanceOf(DashboardOverviewResponse::class);
    expect($result->stats->enrolledCourses)->toBe(0);
    expect($result->stats->hoursThisWeek)->toBe(0.0);
    expect($result->stats->streakDays)->toBe(0);
    expect($result->enrolledCourses)->toBe([]);
    expect($result->recentActivity)->toBe([]);
    expect($result->weeklyPlan)->toHaveCount(4);
});

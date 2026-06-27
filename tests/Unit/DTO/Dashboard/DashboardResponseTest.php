<?php

use App\DTO\Dashboard\DashboardCourseResponse;
use App\DTO\Dashboard\DashboardEnrolledCourseResponse;
use App\DTO\Dashboard\DashboardOverviewResponse;
use App\DTO\Dashboard\DashboardRecentActivityResponse;
use App\DTO\Dashboard\DashboardStatsResponse;
use Tests\TestCase;

uses(TestCase::class);

it('DashboardCourseResponse constructs and serializes', function (): void {
    $dto = new DashboardCourseResponse(
        id: 1,
        name: 'English Course',
        slug: 'english-course',
        thumbnail: 'thumb.jpg',
        price: 500000,
        learned: 10,
        lessonsCount: 20,
        commentsCount: 5,
        totalDurationMinutes: 300,
        category: ['id' => 1, 'name' => 'Giao tiếp', 'slug' => 'giao-tiep'],
    );

    expect($dto->id)->toBe(1);
    expect($dto->toArray())->toMatchArray([
        'id' => 1,
        'name' => 'English Course',
        'price' => 500000,
        'lessons_count' => 20,
    ]);
});

it('DashboardEnrolledCourseResponse constructs and serializes', function (): void {
    $dto = new DashboardEnrolledCourseResponse(
        course: ['id' => 1, 'name' => 'Course'],
        progress: 50,
        instructor: 'Teacher',
        nextLesson: 'Lesson 2',
        lastAccessed: '2 giờ trước',
    );

    expect($dto->progress)->toBe(50);
    expect($dto->toArray())->toMatchArray([
        'course' => ['id' => 1, 'name' => 'Course'],
        'progress' => 50,
        'next_lesson' => 'Lesson 2',
    ]);
});

it('DashboardOverviewResponse constructs and serializes', function (): void {
    $stats = new DashboardStatsResponse(enrolledCourses: 5, hoursThisWeek: 3.5, certificates: 1, streakDays: 4);
    $enrolled = [new DashboardEnrolledCourseResponse(course: ['id' => 1], progress: 50, instructor: 'T', nextLesson: null, lastAccessed: '1h')];
    $activity = [new DashboardRecentActivityResponse(id: 1, type: 'completed', title: 'Lesson', course: 'Course', time: '1h', score: null)];

    $dto = new DashboardOverviewResponse(
        stats: $stats,
        enrolledCourses: $enrolled,
        recentActivity: $activity,
        certificates: [],
        weeklyPlan: [],
    );

    $array = $dto->toArray();
    expect($array['stats']['enrolled_courses'])->toBe(5);
    expect($array['stats']['hours_this_week'])->toBe(3.5);
    expect($array['enrolled_courses'])->toHaveCount(1);
    expect($array['recent_activity'])->toHaveCount(1);
    expect($array['certificates'])->toBe([]);
    expect($array['weekly_plan'])->toBe([]);
});

it('DashboardRecentActivityResponse constructs and serializes', function (): void {
    $dto = new DashboardRecentActivityResponse(id: 3, type: 'completed', title: 'Lesson 1', course: 'Course', time: '5 phút trước', score: 85);

    expect($dto->score)->toBe(85);
    expect($dto->toArray())->toMatchArray([
        'id' => 3,
        'type' => 'completed',
        'title' => 'Lesson 1',
        'score' => 85,
    ]);
});

it('DashboardRecentActivityResponse accepts null score', function (): void {
    $dto = new DashboardRecentActivityResponse(id: 4, type: 'enrolled', title: 'New Course', course: 'Course', time: '1h', score: null);

    expect($dto->score)->toBeNull();
    expect($dto->toArray()['score'])->toBeNull();
});

it('DashboardStatsResponse constructs and serializes', function (): void {
    $dto = new DashboardStatsResponse(enrolledCourses: 3, hoursThisWeek: 1.5, certificates: 0, streakDays: 2);

    expect($dto->toArray())->toMatchArray([
        'enrolled_courses' => 3,
        'hours_this_week' => 1.5,
        'certificates' => 0,
        'streak_days' => 2,
    ]);
});

<?php

use App\Models\Lesson;
use App\Services\Enrollment\IEnrollmentService;
use App\Services\LessonAccess\ILessonAccessService;
use App\Services\LessonAccess\LessonAccessService;
use Tests\TestCase;

uses(TestCase::class);

it('implements service contract', function (): void {
    $enrollmentService = Mockery::mock(IEnrollmentService::class);
    $service = new LessonAccessService($enrollmentService);

    expect($service)->toBeInstanceOf(ILessonAccessService::class);
});

it('allows video access when lesson is preview free', function (): void {
    $enrollmentService = Mockery::mock(IEnrollmentService::class);
    $enrollmentService->shouldReceive('isEnrolled')->never();

    $service = new LessonAccessService($enrollmentService);
    $lesson = new Lesson;
    $lesson->is_preview_free = true;

    expect($service->canWatchLessonVideo(null, $lesson))->toBeTrue();
    expect($service->canWatchLessonVideo(1, $lesson))->toBeTrue();
});

it('denies protected content access when user is not logged in', function (): void {
    $enrollmentService = Mockery::mock(IEnrollmentService::class);
    $enrollmentService->shouldReceive('isEnrolled')->never();

    $service = new LessonAccessService($enrollmentService);
    $lesson = new Lesson;
    $lesson->is_preview_free = false;
    $lesson->course_id = 1;

    expect($service->canAccessLessonProtectedContent(null, $lesson))->toBeFalse();
    expect($service->canWatchLessonVideo(null, $lesson))->toBeFalse();
});

it('denies protected content access when lesson has no course id', function (): void {
    $enrollmentService = Mockery::mock(IEnrollmentService::class);
    $enrollmentService->shouldReceive('isEnrolled')->never();

    $service = new LessonAccessService($enrollmentService);
    $lesson = new Lesson;
    $lesson->is_preview_free = false;
    $lesson->course_id = null;

    expect($service->canAccessLessonProtectedContent(1, $lesson))->toBeFalse();
    expect($service->canWatchLessonVideo(1, $lesson))->toBeFalse();
});

it('defers to enrollment check for non-preview lessons', function (): void {
    $enrollmentService = Mockery::mock(IEnrollmentService::class);
    $enrollmentService->shouldReceive('isEnrolled')
        ->twice()
        ->with(5, 10)
        ->andReturnTrue();

    $service = new LessonAccessService($enrollmentService);
    $lesson = new Lesson;
    $lesson->is_preview_free = false;
    $lesson->course_id = 10;

    expect($service->canAccessLessonProtectedContent(5, $lesson))->toBeTrue();
    expect($service->canWatchLessonVideo(5, $lesson))->toBeTrue();
});

it('returns false when user is not enrolled', function (): void {
    $enrollmentService = Mockery::mock(IEnrollmentService::class);
    $enrollmentService->shouldReceive('isEnrolled')
        ->twice()
        ->with(3, 7)
        ->andReturnFalse();

    $service = new LessonAccessService($enrollmentService);
    $lesson = new Lesson;
    $lesson->is_preview_free = false;
    $lesson->course_id = 7;

    expect($service->canAccessLessonProtectedContent(3, $lesson))->toBeFalse();
    expect($service->canWatchLessonVideo(3, $lesson))->toBeFalse();
});

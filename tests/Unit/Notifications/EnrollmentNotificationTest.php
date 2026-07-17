<?php

use App\Enums\NotificationType;
use App\Notifications\EnrollmentNotification;
use Tests\TestCase;

uses(TestCase::class);

it('uses database channel', function (): void {
    $notification = new EnrollmentNotification(
        courseId: 1,
        courseName: 'IELTS Cơ bản',
    );

    expect($notification->via(new stdClass))->toBe(['database']);
});

it('sends enrollment data', function (): void {
    $notification = new EnrollmentNotification(
        courseId: 5,
        courseName: 'Giao tiếp nâng cao',
        courseThumbnail: 'thumb.jpg',
    );

    $data = $notification->toDatabase(new stdClass);

    expect($data['type'])->toBe(NotificationType::Enrollment->value);
    expect($data['course_id'])->toBe(5);
    expect($data['course_name'])->toBe('Giao tiếp nâng cao');
    expect($data['course_thumbnail'])->toBe('thumb.jpg');
    expect($data['title'])->toBe('Ghi danh khóa học');
    expect($data['body'])->toContain('Giao tiếp nâng cao');
});

it('handles null thumbnail', function (): void {
    $notification = new EnrollmentNotification(
        courseId: 3,
        courseName: 'Khóa học Test',
    );

    $data = $notification->toDatabase(new stdClass);

    expect($data['course_thumbnail'])->toBeNull();
});

it('is queued', function (): void {
    $reflection = new ReflectionClass(EnrollmentNotification::class);
    expect($reflection->implementsInterface(Illuminate\Contracts\Queue\ShouldQueue::class))->toBeTrue();
});

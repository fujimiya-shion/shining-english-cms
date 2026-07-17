<?php

use App\Enums\NotificationType;
use App\Notifications\LessonCompletedNotification;
use Tests\TestCase;

uses(TestCase::class);

it('uses database channel', function (): void {
    $notification = new LessonCompletedNotification(
        courseId: 1,
        courseName: 'IELTS Cơ bản',
        lessonId: 10,
        lessonName: 'Bài 1: Giới thiệu',
    );

    expect($notification->via(new stdClass))->toBe(['database']);
});

it('sends lesson completed data', function (): void {
    $notification = new LessonCompletedNotification(
        courseId: 2,
        courseName: 'Giao tiếp',
        lessonId: 15,
        lessonName: 'Bài 3: Hội thoại',
    );

    $data = $notification->toDatabase(new stdClass);

    expect($data['type'])->toBe(NotificationType::LessonCompleted->value);
    expect($data['course_id'])->toBe(2);
    expect($data['course_name'])->toBe('Giao tiếp');
    expect($data['lesson_id'])->toBe(15);
    expect($data['lesson_name'])->toBe('Bài 3: Hội thoại');
    expect($data['title'])->toBe('Hoàn thành bài học');
    expect($data['body'])->toContain('Bài 3: Hội thoại');
    expect($data['body'])->toContain('Giao tiếp');
});

it('is queued', function (): void {
    $reflection = new ReflectionClass(LessonCompletedNotification::class);
    expect($reflection->implementsInterface(Illuminate\Contracts\Queue\ShouldQueue::class))->toBeTrue();
});

<?php

use App\Filament\Resources\Lessons\Pages\EditLesson;
use App\Models\Course;
use App\Models\Lesson;
use Filament\Actions\Action;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns view on website action when record has course slug', function (): void {
    $course = Course::factory()->create(['slug' => 'test-course']);
    $lesson = Lesson::factory()->create([
        'course_id' => $course->id,
        'video_url' => 'https://example.com/video',
    ]);

    $page = new EditLesson;
    $reflection = new ReflectionProperty($page, 'record');
    $reflection->setValue($page, $lesson);

    $actions = invokeProtectedMethod($page, 'getHeaderActions');
    $viewAction = collect($actions)->first(fn (Action $a) => $a->getName() === 'viewOnWebsite');

    expect($actions)->toHaveCount(1);
    expect($viewAction)->not->toBeNull();
    expect($viewAction->getUrl())->toContain('/courses/test-course?lessonId=');
});

it('returns null url when record has no course', function (): void {
    $lesson = Lesson::factory()->create(['course_id' => null, 'video_url' => 'https://example.com/video']);

    $page = new EditLesson;
    $reflection = new ReflectionProperty($page, 'record');
    $reflection->setValue($page, $lesson);

    $actions = invokeProtectedMethod($page, 'getHeaderActions');

    expect($actions)->toHaveCount(1);
    expect($actions[0]->getUrl())->toBeNull();
});

it('returns empty actions when record is not set', function (): void {
    $page = new EditLesson;
    $actions = rescue(fn () => invokeProtectedMethod($page, 'getHeaderActions'), [], false);

    expect($actions)->toBe([]);
});

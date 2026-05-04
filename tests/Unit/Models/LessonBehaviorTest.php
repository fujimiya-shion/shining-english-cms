<?php

use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonGroup;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('defines lesson group relation', function (): void {
    $method = new ReflectionMethod(Lesson::class, 'lessonGroup');

    expect($method->getReturnType()?->getName())->toBe(BelongsTo::class);
    expect((new Lesson)->lessonGroup())->toBeInstanceOf(BelongsTo::class);
});

it('sets group metadata and auto lesson order from selected lesson group', function (): void {
    $course = Course::factory()->create();
    $group = LessonGroup::query()->create([
        'course_id' => $course->id,
        'name' => 'Module 1',
        'sort_order' => 3,
    ]);

    Lesson::query()->create([
        'name' => 'L1',
        'slug' => 'l1',
        'course_id' => $course->id,
        'lesson_group_id' => $group->id,
        'video_url' => 'lessons/l1.mp4',
        'lesson_order' => 1,
    ]);

    $lesson = Lesson::query()->create([
        'name' => 'L2',
        'slug' => 'l2',
        'course_id' => $course->id,
        'lesson_group_id' => $group->id,
        'video_url' => 'lessons/l2.mp4',
        'lesson_order' => 0,
    ]);

    expect($lesson->group_name)->toBe('Module 1');
    expect($lesson->group_order)->toBe(3);
    expect($lesson->lesson_order)->toBe(2);
});

it('falls back to group_name scope when lesson_group_id is absent', function (): void {
    $course = Course::factory()->create();

    Lesson::query()->create([
        'name' => 'A',
        'slug' => 'a-lesson',
        'course_id' => $course->id,
        'group_name' => 'Legacy',
        'video_url' => 'lessons/a.mp4',
        'lesson_order' => 2,
    ]);

    $lesson = Lesson::query()->create([
        'name' => 'B',
        'slug' => 'b-lesson',
        'course_id' => $course->id,
        'group_name' => 'Legacy',
        'video_url' => 'lessons/b.mp4',
        'lesson_order' => 0,
    ]);

    expect($lesson->lesson_order)->toBe(3);
});

it('keeps lesson order when explicit positive value is provided', function (): void {
    $course = Course::factory()->create();
    $lesson = Lesson::query()->create([
        'name' => 'Explicit order',
        'slug' => 'explicit-order',
        'course_id' => $course->id,
        'video_url' => 'lessons/explicit-order.mp4',
        'lesson_order' => 5,
    ]);

    expect($lesson->lesson_order)->toBe(5);
});

it('keeps values when lesson group relation is loaded as null', function (): void {
    $course = Course::factory()->create();
    $group = LessonGroup::query()->create([
        'course_id' => $course->id,
        'name' => 'Existing group',
        'sort_order' => 7,
    ]);

    $lesson = new Lesson([
        'name' => 'Missing group',
        'slug' => 'missing-group',
        'course_id' => $course->id,
        'lesson_group_id' => $group->id,
        'group_name' => null,
        'group_order' => 0,
        'video_url' => 'lessons/missing-group.mp4',
        'lesson_order' => 0,
    ]);
    $lesson->setRelation('lessonGroup', null);
    $lesson->save();

    expect($lesson->group_name)->toBeNull();
    expect($lesson->group_order)->toBe(0);
    expect($lesson->lesson_order)->toBe(0);
});

it('compute next lesson order returns one when course id is missing', function (): void {
    $lesson = new Lesson([
        'name' => 'No course context',
        'slug' => 'no-course-context',
        'course_id' => null,
    ]);

    $method = new ReflectionMethod(Lesson::class, 'computeNextLessonOrder');
    $method->setAccessible(true);

    expect($method->invoke(null, $lesson))->toBe(1);
});

it('compute next lesson order excludes current lesson when record exists', function (): void {
    $course = Course::factory()->create();
    $group = LessonGroup::query()->create([
        'course_id' => $course->id,
        'name' => 'Module X',
        'sort_order' => 1,
    ]);

    $record = Lesson::query()->create([
        'name' => 'Current',
        'slug' => 'current',
        'course_id' => $course->id,
        'lesson_group_id' => $group->id,
        'video_url' => 'lessons/current.mp4',
        'lesson_order' => 10,
    ]);
    Lesson::query()->create([
        'name' => 'Other',
        'slug' => 'other',
        'course_id' => $course->id,
        'lesson_group_id' => $group->id,
        'video_url' => 'lessons/other.mp4',
        'lesson_order' => 3,
    ]);

    $method = new ReflectionMethod(Lesson::class, 'computeNextLessonOrder');
    $method->setAccessible(true);

    expect($method->invoke(null, $record->fresh()))->toBe(4);
});

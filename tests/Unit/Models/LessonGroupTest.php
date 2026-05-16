<?php

use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonGroup;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('defines fillable and casts attributes', function (): void {
    $model = new LessonGroup;

    expect($model->getFillable())->toEqual([
        'course_id',
        'name',
        'sort_order',
    ]);

    expect($model->getCasts())->toMatchArray([
        'sort_order' => 'integer',
    ]);
});

it('defines course and lessons relations', function (): void {
    $courseMethod = new ReflectionMethod(LessonGroup::class, 'course');
    $lessonsMethod = new ReflectionMethod(LessonGroup::class, 'lessons');

    expect($courseMethod->getReturnType()?->getName())->toBe(BelongsTo::class);
    expect($lessonsMethod->getReturnType()?->getName())->toBe(HasMany::class);

    $model = new LessonGroup;
    expect($model->course())->toBeInstanceOf(BelongsTo::class);
    expect($model->lessons())->toBeInstanceOf(HasMany::class);
});

it('syncs lesson group_name and group_order when group name or sort_order changes', function (): void {
    $course = Course::factory()->create();
    $group = LessonGroup::query()->create([
        'course_id' => $course->id,
        'name' => 'Group A',
        'sort_order' => 1,
    ]);

    $lesson = Lesson::factory()->create([
        'course_id' => $course->id,
        'lesson_group_id' => $group->id,
        'group_name' => 'Old Group',
        'group_order' => 99,
    ]);

    $group->update([
        'name' => 'Group B',
        'sort_order' => 2,
    ]);

    expect($lesson->fresh()->group_name)->toBe('Group B');
    expect($lesson->fresh()->group_order)->toBe(2);
});

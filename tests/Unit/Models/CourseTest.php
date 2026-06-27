<?php

use App\Models\Course;
use App\Models\CourseReview;
use App\Models\Lesson;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('defines fillable attributes', function (): void {
    $model = new Course;

    expect($model->getFillable())->toEqual([
        'name',
        'slug',
        'price',
        'status',
        'thumbnail',
        'category_id',
        'level_id',
        'description',
        'rating',
        'learned',
        'allow_star_payment',
        'star_price',
    ]);
});

it('defines category relation', function (): void {
    $method = new ReflectionMethod(Course::class, 'category');

    expect($method->getReturnType()?->getName())->toBe(BelongsTo::class);
    expect((new Course)->category())->toBeInstanceOf(BelongsTo::class);
});

it('defines lessons relation', function (): void {
    $method = new ReflectionMethod(Course::class, 'lessons');

    expect($method->getReturnType()?->getName())->toBe(HasMany::class);
    expect((new Course)->lessons())->toBeInstanceOf(HasMany::class);
});

it('defines lesson groups relation', function (): void {
    $method = new ReflectionMethod(Course::class, 'lessonGroups');

    expect($method->getReturnType()?->getName())->toBe(HasMany::class);
    expect((new Course)->lessonGroups())->toBeInstanceOf(HasMany::class);
});

it('defines level relation', function (): void {
    $method = new ReflectionMethod(Course::class, 'level');

    expect($method->getReturnType()?->getName())->toBe(BelongsTo::class);
    expect((new Course)->level())->toBeInstanceOf(BelongsTo::class);
});

it('defines enrollments relation', function (): void {
    $method = new ReflectionMethod(Course::class, 'enrollments');

    expect($method->getReturnType()?->getName())->toBe(HasMany::class);
    expect((new Course)->enrollments())->toBeInstanceOf(HasMany::class);
});

it('defines reviews relation', function (): void {
    $method = new ReflectionMethod(Course::class, 'reviews');

    expect($method->getReturnType()?->getName())->toBe(HasMany::class);
    expect((new Course)->reviews())->toBeInstanceOf(HasMany::class);
});

it('uses soft deletes', function (): void {
    $model = new Course;

    expect(method_exists($model, 'trashed'))->toBeTrue();
});

it('normalizes thumbnail URLs and count attributes', function (): void {
    config(['app.url' => 'https://app.test']);

    expect((new Course)->getThumbnailAttribute(null))->toBeNull();
    expect((new Course)->getThumbnailAttribute('https://cdn.test/course.jpg'))->toBe('https://cdn.test/course.jpg');
    expect((new Course)->getThumbnailAttribute('/storage/courses/a.jpg'))->toBe('https://app.test/storage/courses/a.jpg');
    expect((new Course)->getThumbnailAttribute('public/courses/a.jpg'))->toBe('https://app.test/storage/courses/a.jpg');
    expect((new Course)->getThumbnailAttribute('courses/a.jpg'))->toBe('https://app.test/storage/courses/a.jpg');

    $course = new Course;
    expect($course->getLessonsCountAttribute(3))->toBe(3);
    expect($course->lessons_count)->toBe(0);
    expect($course->comments_count)->toBe(0);

    $courseWithAttributes = new Course;
    $courseWithAttributes->setRawAttributes([
        'lessons_count' => 4,
        'reviews_count' => 5,
    ], true);
    expect($courseWithAttributes->lessons_count)->toBe(4);
    expect($courseWithAttributes->comments_count)->toBe(5);

    $courseWithRelations = new Course;
    $courseWithRelations->setRelation('lessons', collect([new Lesson, new Lesson]));
    $courseWithRelations->setRelation('reviews', collect([new CourseReview]));
    expect($courseWithRelations->lessons_count)->toBe(2);
    expect($courseWithRelations->comments_count)->toBe(1);

    $courseWithFallback = new Course;
    $courseWithFallback->setRawAttributes(['lessons_count' => 7], true);
    expect($courseWithFallback->getLessonsCountAttribute(null))->toBe(7);
});

it('applies active scope', function (): void {
    Course::factory()->create(['status' => true, 'name' => 'Visible']);
    Course::factory()->create(['status' => false, 'name' => 'Hidden']);

    expect(Course::query()->active()->pluck('name')->all())->toBe(['Visible']);
});

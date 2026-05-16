<?php

use App\Models\Category;
use App\Models\Course;
use App\Models\CourseReview;
use App\Models\Level;
use App\Models\Lesson;
use App\Models\LessonComment;
use App\Models\User;
use App\Repositories\Course\CourseRepository;
use App\ValueObjects\CourseFilter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class);
uses(RefreshDatabase::class);

it('filters courses by category level ranges and keyword', function (): void {
    $categoryA = Category::factory()->create();
    $categoryB = Category::factory()->create();
    $levelA = Level::factory()->create(['name' => 'Beginner']);
    $levelB = Level::factory()->create(['name' => 'Advanced']);

    Course::factory()->create([
        'category_id' => $categoryA->id,
        'level_id' => $levelA->id,
        'name' => 'Basic English',
        'price' => 200,
        'status' => true,
        'rating' => 4.5,
        'learned' => 15,
    ]);

    Course::factory()->create([
        'category_id' => $categoryA->id,
        'level_id' => $levelB->id,
        'name' => 'Advanced English',
        'price' => 600,
        'status' => true,
        'rating' => 4.9,
        'learned' => 30,
    ]);

    Course::factory()->create([
        'category_id' => $categoryB->id,
        'level_id' => $levelA->id,
        'name' => 'Basic Japanese',
        'price' => 200,
        'status' => false,
        'rating' => 3.2,
        'learned' => 12,
    ]);

    $repository = app(CourseRepository::class);

    $filters = CourseFilter::fromArray([
        'category_id' => $categoryA->id,
        'level_id' => $levelA->id,
        'price_min' => 100,
        'price_max' => 300,
        'rating_min' => 4.0,
        'rating_max' => 5.0,
        'learned_min' => 10,
        'learned_max' => 20,
        'q' => 'basic',
        'page' => 1,
        'perPage' => 15,
    ]);

    $result = $repository->filter($filters);

    expect($result->total())->toBe(1);
    expect($result->items()[0]->name)->toBe('Basic English');
});

it('filters courses with min only conditions', function (): void {
    $category = Category::factory()->create();
    $level = Level::factory()->create();

    Course::factory()->create([
        'category_id' => $category->id,
        'level_id' => $level->id,
        'name' => 'Math 101',
        'price' => 100,
        'status' => true,
        'rating' => 2.5,
        'learned' => 5,
    ]);

    Course::factory()->create([
        'category_id' => $category->id,
        'level_id' => $level->id,
        'name' => 'Math 201',
        'price' => 300,
        'status' => true,
        'rating' => 4.2,
        'learned' => 25,
    ]);

    $repository = app(CourseRepository::class);

    $filters = CourseFilter::fromArray([
        'price_min' => 200,
        'rating_min' => 4.0,
        'learned_min' => 10,
    ]);

    $result = $repository->filter($filters);

    expect($result->total())->toBe(1);
    expect($result->items()[0]->name)->toBe('Math 201');
});

it('filters courses with max only conditions', function (): void {
    $category = Category::factory()->create();
    $level = Level::factory()->create();

    Course::factory()->create([
        'category_id' => $category->id,
        'level_id' => $level->id,
        'name' => 'Science 101',
        'price' => 100,
        'status' => true,
        'rating' => 2.0,
        'learned' => 5,
    ]);

    Course::factory()->create([
        'category_id' => $category->id,
        'level_id' => $level->id,
        'name' => 'Science 201',
        'price' => 300,
        'status' => true,
        'rating' => 4.5,
        'learned' => 25,
    ]);

    $repository = app(CourseRepository::class);

    $filters = CourseFilter::fromArray([
        'price_max' => 200,
        'rating_max' => 3.0,
        'learned_max' => 10,
    ]);

    $result = $repository->filter($filters);

    expect($result->total())->toBe(1);
    expect($result->items()[0]->name)->toBe('Science 101');
});

it('matches keyword in the middle of course name', function (): void {
    $category = Category::factory()->create();
    $level = Level::factory()->create();

    Course::factory()->create([
        'category_id' => $category->id,
        'level_id' => $level->id,
        'name' => 'Basic English',
        'price' => 100,
        'status' => true,
        'rating' => 4.0,
        'learned' => 10,
    ]);

    $repository = app(CourseRepository::class);

    $filters = CourseFilter::fromArray([
        'q' => 'asic',
    ]);

    $result = $repository->filter($filters);

    expect($result->total())->toBe(1);
    expect($result->items()[0]->name)->toBe('Basic English');
});

it('builds filter props from existing courses', function (): void {
    $categoryA = Category::factory()->create([
        'name' => 'Grammar Basics',
        'slug' => 'grammar-basics',
    ]);
    $categoryB = Category::factory()->create([
        'name' => 'Speaking Fluency',
        'slug' => 'speaking-fluency',
    ]);
    $unusedCategory = Category::factory()->create();
    $levelA = Level::factory()->create(['name' => 'Beginner']);
    $levelB = Level::factory()->create(['name' => 'Advanced']);
    $unusedLevel = Level::factory()->create(['name' => 'Unused']);

    Course::factory()->create([
        'category_id' => $categoryA->id,
        'level_id' => $levelA->id,
        'name' => 'Course A',
        'price' => 100,
        'status' => true,
        'rating' => 2.5,
        'learned' => 5,
    ]);
    Course::factory()->create([
        'category_id' => $categoryB->id,
        'level_id' => $levelB->id,
        'name' => 'Course B',
        'price' => 400,
        'status' => false,
        'rating' => 4.5,
        'learned' => 20,
    ]);
    Course::factory()->create([
        'category_id' => $categoryA->id,
        'level_id' => $levelA->id,
        'name' => 'Course C',
        'price' => 250,
        'status' => true,
        'rating' => 3.8,
        'learned' => 12,
    ]);

    $repository = app(CourseRepository::class);

    $props = $repository->getFilterProps();

    expect($props['price'])->toBe(['min' => 100, 'max' => 250]);
    expect($props['rating']['min'])->toBe(2.5);
    expect($props['rating']['max'])->toBe(3.8);
    expect($props['learned'])->toBe(['min' => 5, 'max' => 12]);
    expect($props['levels'])->toContain([
        'value' => $levelA->id,
        'label' => 'Beginner',
        'count' => 2,
    ]);
    expect(collect($props['levels'])->pluck('value')->all())->not()->toContain($levelB->id);
    expect(collect($props['levels'])->pluck('value')->all())->not()->toContain($unusedLevel->id);

    expect($props['categories'])->toContain([
        'id' => $categoryA->id,
        'name' => 'Grammar Basics',
        'slug' => 'grammar-basics',
        'course_count' => 2,
    ]);
    expect(collect($props['categories'])->pluck('id')->all())->not()->toContain($categoryB->id);
    expect(collect($props['categories'])->pluck('id')->all())->not()->toContain($unusedCategory->id);
});

it('gets active course by slug', function (): void {
    $activeCourse = Course::factory()->create([
        'slug' => 'active-course',
        'status' => true,
    ]);
    Course::factory()->create([
        'slug' => 'inactive-course',
        'status' => false,
    ]);

    $repository = app(CourseRepository::class);

    $result = $repository->getBySlug('active-course');
    $inactive = $repository->getBySlug('inactive-course');

    expect($result?->id)->toBe($activeCourse->id);
    expect($inactive)->toBeNull();
});

it('loads reviews and lesson comments when getting course by slug', function (): void {
    $course = Course::factory()->create([
        'slug' => 'course-has-feedback',
        'status' => true,
    ]);

    $lesson = Lesson::query()->create([
        'name' => 'Lesson With Comment',
        'slug' => null,
        'course_id' => $course->id,
        'group_name' => 'Fundamentals',
        'video_url' => 'lessons/example.mp4',
        'documents' => ['lesson-documents/grammar-guide.pdf'],
        'document_names' => ['grammar-guide.pdf'],
        'description' => 'Lesson description',
        'duration_minutes' => 12,
        'star_reward_video' => 1,
        'star_reward_quiz' => 0,
        'has_quiz' => false,
    ]);

    $reviewUser = User::factory()->create(['name' => 'Ha Linh']);
    $commentUser = User::factory()->create(['name' => 'Ngoc Anh']);

    CourseReview::query()->create([
        'course_id' => $course->id,
        'user_id' => $reviewUser->id,
        'rating' => 5,
        'content' => 'Rất tốt',
    ]);

    LessonComment::query()->create([
        'lesson_id' => $lesson->id,
        'user_id' => $commentUser->id,
        'content' => 'Có bài tập không ạ?',
    ]);

    $repository = app(CourseRepository::class);
    $result = $repository->getBySlug('course-has-feedback');

    expect($result)->not()->toBeNull();
    expect($result?->relationLoaded('reviews'))->toBeTrue();
    expect($result?->reviews->count())->toBe(1);
    expect($result?->reviews->first()?->relationLoaded('user'))->toBeTrue();
    expect($result?->reviews->first()?->user?->name)->toBe('Ha Linh');
    expect($result?->relationLoaded('lessons'))->toBeTrue();
    expect($result?->lessons->first()?->documents)->toBe(['lesson-documents/grammar-guide.pdf']);
    expect($result?->lessons->first()?->document_names)->toBe(['grammar-guide.pdf']);
    expect($result?->lessons->first()?->relationLoaded('comments'))->toBeTrue();
    expect($result?->lessons->first()?->comments->count())->toBe(1);
    expect($result?->lessons->first()?->comments->first()?->relationLoaded('user'))->toBeTrue();
    expect($result?->lessons->first()?->comments->first()?->user?->name)->toBe('Ngoc Anh');
});

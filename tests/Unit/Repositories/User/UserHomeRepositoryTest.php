<?php

use App\DTO\User\Page\Home\HomeResponse;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseReview;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Level;
use App\Models\User;
use App\Repositories\User\UserHomeRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class);
uses(RefreshDatabase::class);

it('builds complete home payloads with defaults and enrollment flags', function (): void {
    $category = Category::factory()->create();
    $level = Level::factory()->create();
    $course = Course::factory()->create([
        'category_id' => $category->id,
        'level_id' => $level->id,
        'status' => true,
        'learned' => 20,
        'rating' => 4.5,
    ]);
    Lesson::factory()->create(['course_id' => $course->id]);
    $user = User::factory()->create();
    Enrollment::factory()->create([
        'user_id' => $user->id,
        'course_id' => $course->id,
    ]);
    CourseReview::factory()->create([
        'course_id' => $course->id,
        'user_id' => $user->id,
        'rating' => 5,
        'content' => 'Great course',
    ]);

    $token = $user->createToken('test-token')->plainTextToken;
    $response = (new UserHomeRepository)->getUserHomeData($token);
    $array = $response->toArray();

    expect($response)->toBeInstanceOf(HomeResponse::class);
    expect($array['payloads'])->toHaveCount(8);
    expect(collect($array['payloads'])->pluck('type')->all())->toBe([
        'banner',
        'hero',
        'courses',
        'feature',
        'process',
        'testimonials',
        'statistics',
        'cta',
    ]);

    $coursesPayload = collect($array['payloads'])->firstWhere('type', 'courses');
    expect($coursesPayload['data']['courses'][0]['enrolled'])->toBeTrue();
});

it('resolves user from authenticated request when token is valid', function (): void {
    $user = User::factory()->create();
    $category = Category::factory()->create();
    $level = Level::factory()->create();
    $course = Course::factory()->create([
        'category_id' => $category->id,
        'level_id' => $level->id,
        'status' => true,
    ]);

    request()->setUserResolver(fn () => $user);

    $response = (new UserHomeRepository)->getUserHomeData('some-token');

    expect($response)->toBeInstanceOf(HomeResponse::class);
});

it('returns home data with empty token', function (): void {
    $category = Category::factory()->create();
    $level = Level::factory()->create();
    Course::factory()->create([
        'category_id' => $category->id,
        'level_id' => $level->id,
        'status' => true,
    ]);

    $response = (new UserHomeRepository)->getUserHomeData('');

    expect($response)->toBeInstanceOf(HomeResponse::class);
});

it('formats compact number as string', function (): void {
    $result = invokeProtectedMethod(new UserHomeRepository, 'formatCompactNumber', ['1.5K']);

    expect($result)->toBe('1.5K');
});

it('formats compact number above 1000 as K+', function (): void {
    $result = invokeProtectedMethod(new UserHomeRepository, 'formatCompactNumber', [1500]);

    expect($result)->toBe('1K+');
});

it('formats rating as string', function (): void {
    $result = invokeProtectedMethod(new UserHomeRepository, 'formatRating', ['4.5★']);

    expect($result)->toBe('4.5★');
});

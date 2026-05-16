<?php

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\User;
use App\Models\UserQuizAttempt;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withHeader('Authorization', createDeveloperAccessToken());
});

function createQuizAttemptFixture(): Quiz
{
    $course = Course::factory()->create();

    $lesson = Lesson::query()->create([
        'name' => 'Intro Lesson',
        'slug' => 'intro-lesson',
        'course_id' => $course->id,
        'video_url' => 'https://example.com/video.mp4',
        'has_quiz' => true,
    ]);

    return Quiz::query()->create([
        'lesson_id' => $lesson->id,
        'pass_percent' => 70,
    ]);
}

it('records a quiz attempt for the current user', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('quiz-attempt')->plainTextToken;
    $quiz = createQuizAttemptFixture();

    $response = $this->postJson("/api/v1/quizzes/{$quiz->id}/attempts", [
        'score_percent' => 85,
        'passed' => true,
        'submitted_at' => now()->subMinutes(5)->toISOString(),
    ], [
        'User-Authorization' => $token,
    ]);

    $response->assertStatus(201);
    $response->assertJsonFragment([
        'message' => 'Attempt recorded',
    ]);

    $attempt = UserQuizAttempt::query()
        ->where('user_id', $user->id)
        ->where('quiz_id', $quiz->id)
        ->first();

    expect($attempt)->not->toBeNull();
    expect($attempt->score_percent)->toBe(85.0);
    expect($attempt->passed)->toBeTrue();
});

it('records attempt without submitted_at', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('quiz-attempt')->plainTextToken;
    $quiz = createQuizAttemptFixture();

    $response = $this->postJson("/api/v1/quizzes/{$quiz->id}/attempts", [
        'score_percent' => 72.5,
        'passed' => true,
    ], [
        'User-Authorization' => $token,
    ]);

    $response->assertStatus(201);
    $response->assertJsonFragment([
        'message' => 'Attempt recorded',
    ]);

    $attempt = UserQuizAttempt::query()
        ->where('user_id', $user->id)
        ->where('quiz_id', $quiz->id)
        ->first();

    expect($attempt)->not->toBeNull();
    expect($attempt->score_percent)->toBe(72.5);
});

it('lists attempts for the current user only', function (): void {
    $quiz = createQuizAttemptFixture();
    $user = User::factory()->create();
    $token = $user->createToken('quiz-attempt')->plainTextToken;
    $other = User::factory()->create();

    $userAttempt = UserQuizAttempt::query()->create([
        'user_id' => $user->id,
        'quiz_id' => $quiz->id,
        'score_percent' => 60,
        'passed' => false,
        'submitted_at' => now()->subHours(2),
    ]);

    UserQuizAttempt::query()->create([
        'user_id' => $other->id,
        'quiz_id' => $quiz->id,
        'score_percent' => 90,
        'passed' => true,
        'submitted_at' => now()->subHour(),
    ]);

    $response = $this->getJson("/api/v1/quizzes/{$quiz->id}/attempts", [
        'User-Authorization' => $token,
    ]);

    $response->assertStatus(200);
    $response->assertJsonPath('data.0.id', $userAttempt->id);
    expect($response->json('data'))->toHaveCount(1);
});

it('returns the latest attempt for the current user', function (): void {
    $quiz = createQuizAttemptFixture();
    $user = User::factory()->create();
    $token = $user->createToken('quiz-attempt')->plainTextToken;
    $other = User::factory()->create();

    UserQuizAttempt::query()->create([
        'user_id' => $user->id,
        'quiz_id' => $quiz->id,
        'score_percent' => 50,
        'passed' => false,
        'submitted_at' => now()->subDays(2),
    ]);

    $latest = UserQuizAttempt::query()->create([
        'user_id' => $user->id,
        'quiz_id' => $quiz->id,
        'score_percent' => 80,
        'passed' => true,
        'submitted_at' => now()->subDay(),
    ]);

    UserQuizAttempt::query()->create([
        'user_id' => $other->id,
        'quiz_id' => $quiz->id,
        'score_percent' => 100,
        'passed' => true,
        'submitted_at' => now(),
    ]);

    $response = $this->getJson("/api/v1/quizzes/{$quiz->id}/attempts/latest", [
        'User-Authorization' => $token,
    ]);

    $response->assertStatus(200);
    $response->assertJsonPath('data.id', $latest->id);
});

it('returns not found when latest attempt is missing', function (): void {
    $quiz = createQuizAttemptFixture();
    $user = User::factory()->create();
    $token = $user->createToken('quiz-attempt')->plainTextToken;

    $response = $this->getJson("/api/v1/quizzes/{$quiz->id}/attempts/latest", [
        'User-Authorization' => $token,
    ]);

    $response->assertStatus(404);
    $response->assertJsonFragment([
        'message' => 'Attempt not found',
        'status' => false,
        'status_code' => 404,
    ]);
});

it('validates attempt payload', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('quiz-attempt')->plainTextToken;
    $quiz = createQuizAttemptFixture();

    $response = $this->postJson("/api/v1/quizzes/{$quiz->id}/attempts", [
        'passed' => 'invalid',
    ], [
        'User-Authorization' => $token,
    ]);

    $response->assertStatus(422);
    $response->assertJsonPath('errors.score_percent.0', 'Score is required.');
    $response->assertJsonPath('errors.passed.0', 'Passed must be true or false.');
});

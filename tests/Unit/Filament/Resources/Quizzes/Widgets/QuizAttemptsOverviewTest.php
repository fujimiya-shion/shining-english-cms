<?php

use App\Filament\Resources\Quizzes\Widgets\QuizAttemptsOverview;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\User;
use App\Models\UserQuizAttempt;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('quiz attempts overview returns empty stats when record is missing', function (): void {
    $widget = new QuizAttemptsOverview;

    $stats = invokeProtectedMethod($widget, 'getStats');

    expect($stats)->toBe([]);
});

test('quiz attempts overview computes stats from attempts', function (): void {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $course = Course::factory()->create();
    $lesson = Lesson::factory()->create([
        'course_id' => $course->id,
    ]);
    $quiz = Quiz::query()->create([
        'lesson_id' => $lesson->id,
        'pass_percent' => 70,
    ]);

    UserQuizAttempt::query()->create([
        'user_id' => $userA->id,
        'quiz_id' => $quiz->id,
        'score_percent' => 80,
        'passed' => true,
        'submitted_at' => now()->subMinute(),
    ]);
    UserQuizAttempt::query()->create([
        'user_id' => $userA->id,
        'quiz_id' => $quiz->id,
        'score_percent' => 40,
        'passed' => false,
        'submitted_at' => now()->subSeconds(30),
    ]);
    UserQuizAttempt::query()->create([
        'user_id' => $userB->id,
        'quiz_id' => $quiz->id,
        'score_percent' => 100,
        'passed' => true,
        'submitted_at' => now(),
    ]);

    $widget = new QuizAttemptsOverview;
    $widget->record = $quiz;

    $stats = invokeProtectedMethod($widget, 'getStats');

    expect($stats)->toHaveCount(5);

    $values = array_map(fn ($stat) => $stat->getValue(), $stats);

    expect($values)->toContain('3');
    expect($values)->toContain('66.7%');
    expect($values)->toContain('33.3%');
    expect($values)->toContain('100.0%');
    expect($values)->toContain('40.0%');
});

test('quiz attempts overview uses zero rates and dash scores when no attempts exist', function (): void {
    $course = Course::factory()->create();
    $lesson = Lesson::factory()->create([
        'course_id' => $course->id,
    ]);
    $quiz = Quiz::query()->create([
        'lesson_id' => $lesson->id,
        'pass_percent' => 60,
    ]);

    $widget = new QuizAttemptsOverview;
    $widget->record = $quiz;

    $stats = invokeProtectedMethod($widget, 'getStats');
    $values = array_map(fn ($stat) => $stat->getValue(), $stats);

    expect($values)->toContain('0.0%');
    expect($values)->toContain('-');
});

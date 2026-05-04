<?php

use App\Filament\Resources\Quizzes\Widgets\QuizUserStatsTable;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\User;
use App\Models\UserQuizAttempt;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('quiz user stats table defines expected columns and heading', function (): void {
    $widget = new QuizUserStatsTable;

    $table = $widget->table(makeTable());

    expect(tableColumnNames($table))->toEqual([
        'name',
        'email',
        'submissions_count',
        'pass_rate',
        'passed_count',
        'failed_count',
        'best_score',
        'lowest_score',
        'last_submitted_at',
    ]);

    expect(invokeProtectedMethod($widget, 'getTableHeading'))->toBe('Per-user Attempt Analytics');
});

test('quiz user stats table query returns no data when record is missing', function (): void {
    $widget = new QuizUserStatsTable;

    $query = invokeProtectedMethod($widget, 'buildQuery', [null]);

    expect($query->toSql())->toContain('1 = 0');
});

test('quiz user stats table aggregates per-user stats for a quiz', function (): void {
    $userA = User::factory()->create(['name' => 'Alice']);
    $userB = User::factory()->create(['name' => 'Bob']);

    $course = Course::factory()->create();
    $lesson = Lesson::factory()->create(['course_id' => $course->id]);
    $quiz = Quiz::query()->create([
        'lesson_id' => $lesson->id,
        'pass_percent' => 70,
    ]);

    UserQuizAttempt::query()->create([
        'user_id' => $userA->id,
        'quiz_id' => $quiz->id,
        'score_percent' => 70,
        'passed' => true,
        'submitted_at' => now()->subMinute(),
    ]);
    UserQuizAttempt::query()->create([
        'user_id' => $userA->id,
        'quiz_id' => $quiz->id,
        'score_percent' => 50,
        'passed' => false,
        'submitted_at' => now(),
    ]);
    UserQuizAttempt::query()->create([
        'user_id' => $userB->id,
        'quiz_id' => $quiz->id,
        'score_percent' => 90,
        'passed' => true,
        'submitted_at' => now(),
    ]);

    $query = invokeProtectedMethod(new QuizUserStatsTable, 'buildQuery', [$quiz->id]);
    $rows = $query->get()->keyBy('name');

    expect($rows)->toHaveCount(2);

    expect((int) $rows['Alice']->submissions_count)->toBe(2);
    expect((int) $rows['Alice']->passed_count)->toBe(1);
    expect((int) $rows['Alice']->failed_count)->toBe(1);

    expect((int) $rows['Bob']->submissions_count)->toBe(1);
    expect((int) $rows['Bob']->passed_count)->toBe(1);
    expect((int) $rows['Bob']->failed_count)->toBe(0);
});

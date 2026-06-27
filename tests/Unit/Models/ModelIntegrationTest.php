<?php

use App\Enums\StarTransactionType;
use App\Models\Category;
use App\Models\City;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizQuestion;
use App\Models\Star;
use App\Models\StarTransaction;
use App\Models\User;
use App\Models\UserQuizAttempt;
use Illuminate\Foundation\Testing\RefreshDatabase;

if (! extension_loaded('pdo')) {
    it('skips model integration tests when PDO is unavailable', function (): void {
        $this->markTestSkipped('PDO extension is required for model integration tests.');
    });

    return;
}

uses(RefreshDatabase::class);

it('resolves model relations across category course lesson quiz question answer and attempt', function (): void {
    $city = City::query()->create([
        'name' => 'Ho Chi Minh',
        'sort_order' => 1,
    ]);

    $user = User::query()->create([
        'name' => 'Test User',
        'nickname' => 'Tester',
        'email' => 'test@example.com',
        'phone' => '0123456789',
        'city_id' => $city->id,
        'password' => 'secret123',
    ]);

    $category = Category::query()->create([
        'name' => 'Grammar',
        'slug' => 'grammar',
    ]);

    $course = Course::query()->create([
        'name' => 'Basic Grammar',
        'price' => 100000,
        'status' => true,
        'category_id' => $category->id,
    ]);

    $lesson = Lesson::query()->create([
        'name' => 'Lesson 1',
        'course_id' => $course->id,
        'video_url' => 'https://example.com/video',
        'star_reward_video' => 10,
        'star_reward_quiz' => 20,
        'has_quiz' => true,
    ]);

    $quiz = Quiz::query()->create([
        'lesson_id' => $lesson->id,
        'pass_percent' => 80,
    ]);

    $question = QuizQuestion::query()->create([
        'quiz_id' => $quiz->id,
        'content' => 'What is the correct answer?',
    ]);

    $answer = QuizAnswer::query()->create([
        'quiz_question_id' => $question->id,
        'content' => 'This is the answer.',
    ]);

    $attempt = UserQuizAttempt::query()->create([
        'user_id' => $user->id,
        'quiz_id' => $quiz->id,
        'score_percent' => 87.5,
        'passed' => true,
        'submitted_at' => now(),
    ]);

    expect($category->courses)->toHaveCount(1);
    expect($course->category->is($category))->toBeTrue();
    expect($course->lessons)->toHaveCount(1);
    expect($lesson->course->is($course))->toBeTrue();
    expect($lesson->quiz->is($quiz))->toBeTrue();
    expect($quiz->lesson->is($lesson))->toBeTrue();
    expect($quiz->questions)->toHaveCount(1);
    expect($quiz->attempts)->toHaveCount(1);
    expect($question->quiz->is($quiz))->toBeTrue();
    expect($question->answers)->toHaveCount(1);
    expect($answer->question->is($question))->toBeTrue();
    expect($user->city->is($city))->toBeTrue();
    expect($user->nickname)->toBe('Tester');
    expect($city->users)->toHaveCount(1);
    expect($user->quizAttempts)->toHaveCount(1);
    expect($attempt->user->is($user))->toBeTrue();
    expect($attempt->quiz->is($quiz))->toBeTrue();
});

it('casts user quiz attempt attributes correctly', function (): void {
    $city = City::query()->create([
        'name' => 'Hanoi',
        'sort_order' => 2,
    ]);

    $user = User::query()->create([
        'name' => 'Cast User',
        'email' => 'cast@example.com',
        'phone' => '0987654321',
        'city_id' => $city->id,
        'password' => 'secret123',
    ]);

    $category = Category::query()->create([
        'name' => 'Vocabulary',
        'slug' => 'vocabulary',
    ]);

    $course = Course::query()->create([
        'name' => 'Vocabulary Basics',
        'price' => 200000,
        'status' => true,
        'category_id' => $category->id,
    ]);

    $lesson = Lesson::query()->create([
        'name' => 'Vocabulary Lesson',
        'course_id' => $course->id,
        'video_url' => 'https://example.com/vocabulary',
        'star_reward_video' => 5,
        'star_reward_quiz' => 15,
        'has_quiz' => true,
    ]);

    $quiz = Quiz::query()->create([
        'lesson_id' => $lesson->id,
        'pass_percent' => 75,
    ]);

    $attempt = UserQuizAttempt::query()->create([
        'user_id' => $user->id,
        'quiz_id' => $quiz->id,
        'score_percent' => '90.1',
        'passed' => 1,
        'submitted_at' => now()->toDateTimeString(),
    ])->refresh();

    expect($attempt->score_percent)->toBeFloat();
    expect($attempt->passed)->toBeBool();
    expect($attempt->submitted_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
});

it('casts star transaction type and links star models to users', function (): void {
    $city = City::query()->create([
        'name' => 'Hai Phong',
        'sort_order' => 4,
    ]);

    $user = User::query()->create([
        'name' => 'Star User',
        'email' => 'star@example.com',
        'phone' => '0111222333',
        'city_id' => $city->id,
        'password' => 'secret123',
    ]);

    $star = Star::query()->create([
        'user_id' => $user->id,
        'amount' => '10',
    ])->refresh();

    $transaction = StarTransaction::query()->create([
        'user_id' => $user->id,
        'amount' => '5',
        'type' => StarTransactionType::Increase,
        'description' => 'Initial reward',
    ])->refresh();

    expect($star->amount)->toBeInt();
    expect($star->user->is($user))->toBeTrue();
    expect($transaction->amount)->toBeInt();
    expect($transaction->type)->toBeInstanceOf(StarTransactionType::class);
    expect($transaction->type)->toBe(StarTransactionType::Increase);
    expect($transaction->user->is($user))->toBeTrue();
});

it('supports soft delete on models using soft deletes', function (): void {
    $category = Category::query()->create([
        'name' => 'Pronunciation',
        'slug' => 'pronunciation',
    ]);
    $city = City::query()->create(['name' => 'Da Nang', 'sort_order' => 3]);

    $course = Course::query()->create([
        'name' => 'Pronunciation 101',
        'price' => 150000,
        'status' => true,
        'category_id' => $category->id,
    ]);

    $lesson = Lesson::query()->create([
        'name' => 'Pronunciation Intro',
        'course_id' => $course->id,
        'video_url' => 'https://example.com/pronunciation',
        'star_reward_video' => 1,
        'star_reward_quiz' => 2,
        'has_quiz' => true,
    ]);

    $quiz = Quiz::query()->create([
        'lesson_id' => $lesson->id,
    ]);

    $question = QuizQuestion::query()->create([
        'quiz_id' => $quiz->id,
        'content' => 'Choose the correct pronunciation',
    ]);

    $answer = QuizAnswer::query()->create([
        'quiz_question_id' => $question->id,
        'content' => 'Option A',
    ]);

    $category->delete();
    $city->delete();
    $course->delete();
    $lesson->delete();
    $quiz->delete();
    $question->delete();
    $answer->delete();

    expect(Category::query()->count())->toBe(0);
    expect(Category::withTrashed()->count())->toBe(1);
    expect(City::query()->count())->toBe(0);
    expect(City::withTrashed()->count())->toBe(1);
    expect(Course::query()->count())->toBe(0);
    expect(Course::withTrashed()->count())->toBe(1);
    expect(Lesson::query()->count())->toBe(0);
    expect(Lesson::withTrashed()->count())->toBe(1);
    expect(Quiz::query()->count())->toBe(0);
    expect(Quiz::withTrashed()->count())->toBe(1);
    expect(QuizQuestion::query()->count())->toBe(0);
    expect(QuizQuestion::withTrashed()->count())->toBe(1);
    expect(QuizAnswer::query()->count())->toBe(0);
    expect(QuizAnswer::withTrashed()->count())->toBe(1);
});

it('generates slug on create for course and lesson', function (): void {
    $category = Category::query()->create([
        'name' => 'Listening',
        'slug' => 'listening',
    ]);

    $course = Course::query()->create([
        'name' => 'Beginner Listening',
        'price' => 99000,
        'status' => true,
        'category_id' => $category->id,
    ]);

    $lesson = Lesson::query()->create([
        'name' => 'Listening Intro',
        'course_id' => $course->id,
        'video_url' => 'https://example.com/listening',
        'star_reward_video' => 3,
        'star_reward_quiz' => 3,
        'has_quiz' => false,
    ]);

    expect($course->slug)->toBe('beginner-listening');
    expect($lesson->slug)->toBe('listening-intro');
});

it('keeps provided slug on create and creates unique slug for duplicates', function (): void {
    $category = Category::query()->create([
        'name' => 'Reading',
        'slug' => 'reading',
    ]);

    $manual = Course::query()->create([
        'name' => 'Reading Master',
        'slug' => 'custom-reading',
        'price' => 120000,
        'status' => true,
        'category_id' => $category->id,
    ]);

    $first = Course::query()->create([
        'name' => 'Reading Skill',
        'price' => 120000,
        'status' => true,
        'category_id' => $category->id,
    ]);

    $second = Course::query()->create([
        'name' => 'Reading Skill',
        'price' => 120000,
        'status' => true,
        'category_id' => $category->id,
    ]);

    expect($manual->slug)->toBe('custom-reading');
    expect($first->slug)->toBe('reading-skill');
    expect($second->slug)->toBe('reading-skill-1');
});

it('updates slug when source changes unless slug is manually changed', function (): void {
    $category = Category::query()->create([
        'name' => 'Speaking',
        'slug' => 'speaking',
    ]);

    $course = Course::query()->create([
        'name' => 'Speaking Basic',
        'price' => 150000,
        'status' => true,
        'category_id' => $category->id,
    ]);

    $course->update(['name' => 'Speaking Advanced']);
    expect($course->fresh()->slug)->toBe('speaking-advanced');

    $course->update([
        'name' => 'Speaking Pro',
        'slug' => 'manual-speaking-pro',
    ]);
    expect($course->fresh()->slug)->toBe('manual-speaking-pro');
});

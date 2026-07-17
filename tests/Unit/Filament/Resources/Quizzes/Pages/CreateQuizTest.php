<?php

use App\Filament\Resources\Quizzes\Pages\CreateQuiz;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizQuestion;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->quiz = Quiz::query()->create([
        'lesson_id' => null,
        'name' => 'Sample Quiz',
        'pass_percent' => 70,
        'order' => 0,
    ]);

    $this->page = new CreateQuiz;

    $ref = new ReflectionProperty($this->page, 'record');
    $ref->setValue($this->page, $this->quiz);
});

it('creates questions and answers from json string', function (): void {
    $data = [
        'questions' => json_encode([
            [
                'content' => 'Question 1',
                'sort_order' => 0,
                'answers' => [
                    ['content' => 'Answer A', 'is_correct' => true, 'sort_order' => 0],
                    ['content' => 'Answer B', 'is_correct' => false, 'sort_order' => 1],
                ],
            ],
            [
                'content' => 'Question 2',
                'sort_order' => 1,
                'answers' => [
                    ['content' => 'Answer C', 'is_correct' => true, 'sort_order' => 0],
                ],
            ],
        ]),
    ];

    $ref = new ReflectionProperty($this->page, 'data');
    $ref->setValue($this->page, $data);

    invokeProtectedMethod($this->page, 'syncQuestions');

    $this->quiz->load('questions.answers');

    expect($this->quiz->questions)->toHaveCount(2);
    expect($this->quiz->questions[0]->content)->toBe('Question 1');
    expect($this->quiz->questions[0]->answers)->toHaveCount(2);
    expect($this->quiz->questions[0]->answers[0]->is_correct)->toBeTrue();
    expect($this->quiz->questions[0]->answers[1]->is_correct)->toBeFalse();
    expect($this->quiz->questions[1]->content)->toBe('Question 2');
    expect($this->quiz->questions[1]->answers)->toHaveCount(1);
    expect($this->quiz->questions[1]->answers[0]->is_correct)->toBeTrue();
});

it('creates questions and answers from array', function (): void {
    $data = [
        'questions' => [
            [
                'content' => 'Q from array',
                'answers' => [
                    ['content' => 'A1', 'is_correct' => true],
                ],
            ],
        ],
    ];

    $ref = new ReflectionProperty($this->page, 'data');
    $ref->setValue($this->page, $data);

    invokeProtectedMethod($this->page, 'syncQuestions');

    expect($this->quiz->questions()->count())->toBe(1);
    expect($this->quiz->questions()->first()->answers()->count())->toBe(1);
});

it('force deletes old questions before recreating', function (): void {
    $oldQuestion = QuizQuestion::query()->create([
        'quiz_id' => $this->quiz->id,
        'content' => 'Old Q',
        'sort_order' => 0,
    ]);

    $data = [
        'questions' => json_encode([
            [
                'content' => 'New Q',
                'sort_order' => 0,
                'answers' => [
                    ['content' => 'New A', 'is_correct' => true, 'sort_order' => 0],
                ],
            ],
        ]),
    ];

    $ref = new ReflectionProperty($this->page, 'data');
    $ref->setValue($this->page, $data);

    invokeProtectedMethod($this->page, 'syncQuestions');

    expect(QuizQuestion::query()->where('quiz_id', $this->quiz->id)->count())->toBe(1);
    expect(QuizQuestion::query()->where('quiz_id', $this->quiz->id)->first()->content)->toBe('New Q');
    expect(QuizQuestion::query()->where('id', $oldQuestion->id)->count())->toBe(0);
});

it('handles empty questions json', function (): void {
    $data = ['questions' => json_encode([])];

    $ref = new ReflectionProperty($this->page, 'data');
    $ref->setValue($this->page, $data);

    invokeProtectedMethod($this->page, 'syncQuestions');

    expect($this->quiz->questions()->count())->toBe(0);
});

it('handles null questions', function (): void {
    $data = ['questions' => null];

    $ref = new ReflectionProperty($this->page, 'data');
    $ref->setValue($this->page, $data);

    invokeProtectedMethod($this->page, 'syncQuestions');

    expect($this->quiz->questions()->count())->toBe(0);
});

it('handles missing questions key', function (): void {
    $data = [];

    $ref = new ReflectionProperty($this->page, 'data');
    $ref->setValue($this->page, $data);

    invokeProtectedMethod($this->page, 'syncQuestions');

    expect($this->quiz->questions()->count())->toBe(0);
});

it('falls back sort_order to index when not provided', function (): void {
    $data = [
        'questions' => json_encode([
            ['content' => 'First', 'answers' => [['content' => 'A', 'is_correct' => true]]],
            ['content' => 'Second', 'answers' => [['content' => 'B', 'is_correct' => true]]],
        ]),
    ];

    $ref = new ReflectionProperty($this->page, 'data');
    $ref->setValue($this->page, $data);

    invokeProtectedMethod($this->page, 'syncQuestions');

    $questions = $this->quiz->questions()->orderBy('id')->get();
    expect($questions[0]->sort_order)->toBe(0);
    expect($questions[1]->sort_order)->toBe(1);
});

it('casts is_correct to boolean', function (): void {
    $data = [
        'questions' => json_encode([
            [
                'content' => 'Q',
                'sort_order' => 0,
                'answers' => [
                    ['content' => 'Truthy', 'is_correct' => 1, 'sort_order' => 0],
                    ['content' => 'Falsy', 'is_correct' => 0, 'sort_order' => 1],
                    ['content' => 'Null', 'sort_order' => 2],
                ],
            ],
        ]),
    ];

    $ref = new ReflectionProperty($this->page, 'data');
    $ref->setValue($this->page, $data);

    invokeProtectedMethod($this->page, 'syncQuestions');

    $answers = $this->quiz->questions()->first()->answers()->orderBy('id')->get();
    expect($answers[0]->is_correct)->toBeTrue();
    expect($answers[1]->is_correct)->toBeFalse();
    expect($answers[2]->is_correct)->toBeFalse();
});

it('calls syncQuestions from afterCreate', function (): void {
    $data = [
        'questions' => json_encode([
            [
                'content' => 'AfterCreate Q',
                'sort_order' => 0,
                'answers' => [
                    ['content' => 'AfterCreate A', 'is_correct' => true, 'sort_order' => 0],
                ],
            ],
        ]),
    ];

    $ref = new ReflectionProperty($this->page, 'data');
    $ref->setValue($this->page, $data);

    invokeProtectedMethod($this->page, 'afterCreate');

    expect($this->quiz->questions()->count())->toBe(1);
    expect($this->quiz->questions()->first()->answers()->count())->toBe(1);
});

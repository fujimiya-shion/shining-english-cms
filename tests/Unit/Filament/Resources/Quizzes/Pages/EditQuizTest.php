<?php

use App\Filament\Resources\Quizzes\Pages\EditQuiz;
use App\Filament\Resources\Quizzes\Widgets\QuizAttemptsOverview;
use App\Filament\Resources\Quizzes\Widgets\QuizUserStatsTable;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizQuestion;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->quiz = Quiz::query()->create([
        'lesson_id' => null,
        'name' => 'Test Quiz',
        'pass_percent' => 70,
        'order' => 0,
    ]);

    $this->page = new EditQuiz;

    $ref = new ReflectionProperty($this->page, 'record');
    $ref->setValue($this->page, $this->quiz);
});

test('edit quiz page defines header widgets', function (): void {
    $page = new EditQuiz;

    $widgets = invokeProtectedMethod($page, 'getHeaderWidgets');

    expect($widgets)->toEqual([
        QuizAttemptsOverview::class,
        QuizUserStatsTable::class,
    ]);
});

test('edit quiz page defines single widget column', function (): void {
    $page = new EditQuiz;

    expect($page->getHeaderWidgetsColumns())->toBe(1);
});

test('edit quiz page passes current record to widgets', function (): void {
    $page = new EditQuiz;

    $quiz = new Quiz(['id' => 99]);

    $reflection = new ReflectionProperty($page, 'record');
    $reflection->setValue($page, $quiz);

    expect($page->getWidgetData())->toMatchArray([
        'record' => $quiz,
    ]);
});

// mutateFormDataBeforeFill tests

test('mutateFormDataBeforeFill returns empty json when quiz has no questions', function (): void {
    $data = invokeProtectedMethod($this->page, 'mutateFormDataBeforeFill', [['name' => 'Test Quiz']]);

    expect($data['questions'])->toBe(json_encode([]));
});

test('mutateFormDataBeforeFill loads questions with answers in order', function (): void {
    $q1 = QuizQuestion::query()->create([
        'quiz_id' => $this->quiz->id,
        'content' => 'Second Q',
        'sort_order' => 1,
    ]);
    $q2 = QuizQuestion::query()->create([
        'quiz_id' => $this->quiz->id,
        'content' => 'First Q',
        'sort_order' => 0,
    ]);

    QuizAnswer::query()->create([
        'quiz_question_id' => $q2->id,
        'content' => 'Correct A',
        'is_correct' => true,
        'sort_order' => 0,
    ]);

    $data = invokeProtectedMethod($this->page, 'mutateFormDataBeforeFill', [['name' => 'Test Quiz']]);

    $questions = json_decode($data['questions'], true);

    expect($questions)->toHaveCount(2);
    expect($questions[0]['content'])->toBe('First Q');
    expect($questions[1]['content'])->toBe('Second Q');
    expect($questions[0]['answers'])->toHaveCount(1);
    expect($questions[0]['answers'][0]['content'])->toBe('Correct A');
});

test('mutateFormDataBeforeFill defaults sort_order to 0', function (): void {
    QuizQuestion::query()->create([
        'quiz_id' => $this->quiz->id,
        'content' => 'No order',
        'sort_order' => null,
    ]);

    $data = invokeProtectedMethod($this->page, 'mutateFormDataBeforeFill', [['name' => 'Test Quiz']]);

    $questions = json_decode($data['questions'], true);
    expect($questions[0]['sort_order'])->toBe(0);
});

test('mutateFormDataBeforeFill casts is_correct to boolean', function (): void {
    $q = QuizQuestion::query()->create([
        'quiz_id' => $this->quiz->id,
        'content' => 'Q',
        'sort_order' => 0,
    ]);

    QuizAnswer::query()->create([
        'quiz_question_id' => $q->id,
        'content' => 'Truthy',
        'is_correct' => 1,
        'sort_order' => 0,
    ]);
    QuizAnswer::query()->create([
        'quiz_question_id' => $q->id,
        'content' => 'Falsy',
        'is_correct' => 0,
        'sort_order' => 1,
    ]);

    $data = invokeProtectedMethod($this->page, 'mutateFormDataBeforeFill', [['name' => 'Test Quiz']]);

    $questions = json_decode($data['questions'], true);
    expect($questions[0]['answers'][0]['is_correct'])->toBeTrue();
    expect($questions[0]['answers'][1]['is_correct'])->toBeFalse();
});

// syncQuestions tests

test('syncQuestions creates new questions and answers from json string', function (): void {
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

    $this->quiz->load('questions.answers');
    expect($this->quiz->questions)->toHaveCount(1);
    expect($this->quiz->questions[0]->answers)->toHaveCount(1);
    expect($this->quiz->questions[0]->answers[0]->is_correct)->toBeTrue();
});

test('syncQuestions updates existing question content', function (): void {
    $q = QuizQuestion::query()->create([
        'quiz_id' => $this->quiz->id,
        'content' => 'Original',
        'sort_order' => 0,
    ]);

    $data = [
        'questions' => json_encode([
            [
                'id' => $q->id,
                'content' => 'Updated',
                'sort_order' => 0,
                'answers' => [],
            ],
        ]),
    ];

    $ref = new ReflectionProperty($this->page, 'data');
    $ref->setValue($this->page, $data);

    invokeProtectedMethod($this->page, 'syncQuestions');

    expect($q->fresh()->content)->toBe('Updated');
});

test('syncQuestions deletes questions not in the submitted set', function (): void {
    $q1 = QuizQuestion::query()->create([
        'quiz_id' => $this->quiz->id,
        'content' => 'Keep',
        'sort_order' => 0,
    ]);
    $q2 = QuizQuestion::query()->create([
        'quiz_id' => $this->quiz->id,
        'content' => 'Delete',
        'sort_order' => 1,
    ]);

    $data = [
        'questions' => json_encode([
            [
                'id' => $q1->id,
                'content' => 'Keep',
                'sort_order' => 0,
                'answers' => [],
            ],
        ]),
    ];

    $ref = new ReflectionProperty($this->page, 'data');
    $ref->setValue($this->page, $data);

    invokeProtectedMethod($this->page, 'syncQuestions');

    expect(QuizQuestion::query()->where('quiz_id', $this->quiz->id)->count())->toBe(1);
    expect(QuizQuestion::query()->find($q1->id)->exists())->toBeTrue();
});

test('syncQuestions updates existing answer content', function (): void {
    $q = QuizQuestion::query()->create([
        'quiz_id' => $this->quiz->id,
        'content' => 'Q',
        'sort_order' => 0,
    ]);
    $a = QuizAnswer::query()->create([
        'quiz_question_id' => $q->id,
        'content' => 'Original',
        'is_correct' => false,
        'sort_order' => 0,
    ]);

    $data = [
        'questions' => json_encode([
            [
                'id' => $q->id,
                'content' => 'Q',
                'sort_order' => 0,
                'answers' => [
                    ['id' => $a->id, 'content' => 'Updated', 'is_correct' => true, 'sort_order' => 0],
                ],
            ],
        ]),
    ];

    $ref = new ReflectionProperty($this->page, 'data');
    $ref->setValue($this->page, $data);

    invokeProtectedMethod($this->page, 'syncQuestions');

    expect($a->fresh()->content)->toBe('Updated');
    expect($a->fresh()->is_correct)->toBeTrue();
});

test('syncQuestions deletes answers not in the submitted set', function (): void {
    $q = QuizQuestion::query()->create([
        'quiz_id' => $this->quiz->id,
        'content' => 'Q',
        'sort_order' => 0,
    ]);
    $a1 = QuizAnswer::query()->create([
        'quiz_question_id' => $q->id,
        'content' => 'Keep',
        'is_correct' => true,
        'sort_order' => 0,
    ]);
    $a2 = QuizAnswer::query()->create([
        'quiz_question_id' => $q->id,
        'content' => 'Delete',
        'is_correct' => false,
        'sort_order' => 1,
    ]);

    $data = [
        'questions' => json_encode([
            [
                'id' => $q->id,
                'content' => 'Q',
                'sort_order' => 0,
                'answers' => [
                    ['id' => $a1->id, 'content' => 'Keep', 'is_correct' => true, 'sort_order' => 0],
                ],
            ],
        ]),
    ];

    $ref = new ReflectionProperty($this->page, 'data');
    $ref->setValue($this->page, $data);

    invokeProtectedMethod($this->page, 'syncQuestions');

    expect(QuizAnswer::query()->where('quiz_question_id', $q->id)->count())->toBe(1);
    expect(QuizAnswer::query()->find($a1->id)->exists())->toBeTrue();
});

test('syncQuestions handles empty questions array', function (): void {
    $q = QuizQuestion::query()->create([
        'quiz_id' => $this->quiz->id,
        'content' => 'To be deleted',
        'sort_order' => 0,
    ]);

    $data = ['questions' => json_encode([])];

    $ref = new ReflectionProperty($this->page, 'data');
    $ref->setValue($this->page, $data);

    invokeProtectedMethod($this->page, 'syncQuestions');

    expect(QuizQuestion::query()->where('quiz_id', $this->quiz->id)->count())->toBe(0);
});

test('syncQuestions handles null questions', function (): void {
    $data = ['questions' => null];

    $ref = new ReflectionProperty($this->page, 'data');
    $ref->setValue($this->page, $data);

    invokeProtectedMethod($this->page, 'syncQuestions');

    expect($this->quiz->questions()->count())->toBe(0);
});

test('syncQuestions handles missing questions key', function (): void {
    $data = [];

    $ref = new ReflectionProperty($this->page, 'data');
    $ref->setValue($this->page, $data);

    invokeProtectedMethod($this->page, 'syncQuestions');

    expect($this->quiz->questions()->count())->toBe(0);
});

test('syncQuestions falls back sort_order to index', function (): void {
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

test('syncQuestions casts is_correct to boolean', function (): void {
    $data = [
        'questions' => json_encode([
            [
                'content' => 'Q',
                'sort_order' => 0,
                'answers' => [
                    ['content' => 'Falsy', 'is_correct' => 0, 'sort_order' => 0],
                ],
            ],
        ]),
    ];

    $ref = new ReflectionProperty($this->page, 'data');
    $ref->setValue($this->page, $data);

    invokeProtectedMethod($this->page, 'syncQuestions');

    expect($this->quiz->questions()->first()->answers()->first()->is_correct)->toBeFalse();
});

test('afterSave calls syncQuestions', function (): void {
    $data = [
        'questions' => json_encode([
            [
                'content' => 'AfterSave Q',
                'sort_order' => 0,
                'answers' => [
                    ['content' => 'AfterSave A', 'is_correct' => true, 'sort_order' => 0],
                ],
            ],
        ]),
    ];

    $ref = new ReflectionProperty($this->page, 'data');
    $ref->setValue($this->page, $data);

    invokeProtectedMethod($this->page, 'afterSave');

    expect($this->quiz->questions()->count())->toBe(1);
    expect($this->quiz->questions()->first()->answers()->count())->toBe(1);
});

test('syncQuestions processes data from array input', function (): void {
    $data = [
        'questions' => [
            [
                'content' => 'Array Q',
                'sort_order' => 0,
                'answers' => [
                    ['content' => 'Array A', 'is_correct' => true, 'sort_order' => 0],
                ],
            ],
        ],
    ];

    $ref = new ReflectionProperty($this->page, 'data');
    $ref->setValue($this->page, $data);

    invokeProtectedMethod($this->page, 'syncQuestions');

    expect($this->quiz->questions()->count())->toBe(1);
    expect($this->quiz->questions()->first()->answers()->count())->toBe(1);
    expect($this->quiz->questions()->first()->answers()->first()->is_correct)->toBeTrue();
});

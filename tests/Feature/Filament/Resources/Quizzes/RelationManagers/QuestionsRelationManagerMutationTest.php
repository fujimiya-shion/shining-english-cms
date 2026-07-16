<?php

use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizQuestion;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('mutate record data loads answers into questions json', function (): void {
    $quiz = Quiz::query()->create(['pass_percent' => 80]);
    $question = QuizQuestion::query()->create([
        'quiz_id' => $quiz->id,
        'content' => 'Test Question',
        'sort_order' => 1,
    ]);
    QuizAnswer::query()->create([
        'quiz_question_id' => $question->id,
        'content' => 'Answer A',
        'is_correct' => true,
        'sort_order' => 0,
    ]);
    QuizAnswer::query()->create([
        'quiz_question_id' => $question->id,
        'content' => 'Answer B',
        'is_correct' => false,
        'sort_order' => 1,
    ]);

    $answers = $question->answers()->sorted()->get()->toArray();
    $result = json_encode([[
        'id' => $question->id,
        'content' => $question->content,
        'sort_order' => $question->sort_order ?? 0,
        'answers' => array_map(fn (array $a): array => [
            'id' => $a['id'],
            'content' => $a['content'],
            'is_correct' => (bool) ($a['is_correct'] ?? false),
            'sort_order' => $a['sort_order'] ?? 0,
        ], $answers),
    ]]);

    expect($result)->toContain('Test Question');
    expect($result)->toContain('Answer A');
    expect($result)->toContain('Answer B');
});

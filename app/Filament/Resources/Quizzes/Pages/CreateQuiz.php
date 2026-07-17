<?php

namespace App\Filament\Resources\Quizzes\Pages;

use App\Filament\Resources\Quizzes\QuizResource;
use App\Models\QuizQuestion;
use Filament\Resources\Pages\CreateRecord;

class CreateQuiz extends CreateRecord
{
    protected static string $resource = QuizResource::class;

    protected function afterCreate(): void
    {
        $this->syncQuestions();
    }

    protected function syncQuestions(): void
    {
        $quiz = $this->record;
        $raw = $this->data['questions'] ?? '[]';
        $questions = is_string($raw) ? json_decode($raw, true) : ($raw ?? []);

        QuizQuestion::query()->where('quiz_id', $quiz->id)->forceDelete();

        foreach ($questions as $qIndex => $questionData) {
            $question = $quiz->questions()->create([
                'content' => $questionData['content'] ?? '',
                'sort_order' => $questionData['sort_order'] ?? $qIndex,
            ]);

            foreach ($questionData['answers'] ?? [] as $aIndex => $answerData) {
                $question->answers()->create([
                    'content' => $answerData['content'] ?? '',
                    'is_correct' => (bool) ($answerData['is_correct'] ?? false),
                    'sort_order' => $answerData['sort_order'] ?? $aIndex,
                ]);
            }
        }
    }
}

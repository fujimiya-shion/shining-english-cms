<?php

namespace App\Filament\Resources\Quizzes\Pages;

use App\Filament\Resources\Quizzes\QuizResource;
use App\Filament\Resources\Quizzes\Widgets\QuizAttemptsOverview;
use App\Filament\Resources\Quizzes\Widgets\QuizUserStatsTable;
use App\Models\QuizAnswer;
use App\Models\QuizQuestion;
use Filament\Resources\Pages\EditRecord;

class EditQuiz extends EditRecord
{
    protected static string $resource = QuizResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $quiz = $this->record;
        $questions = QuizQuestion::query()
            ->where('quiz_id', $quiz->id)
            ->with('answers')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->toArray();

        $data['questions'] = json_encode(array_map(function (array $q): array {
            $q['sort_order'] = $q['sort_order'] ?? 0;
            foreach ($q['answers'] as &$answer) {
                $answer['is_correct'] = (bool) ($answer['is_correct'] ?? false);
                $answer['sort_order'] = $answer['sort_order'] ?? 0;
            }

            return $q;
        }, $questions));

        return $data;
    }

    protected function afterSave(): void
    {
        $this->syncQuestions();
    }

    protected function syncQuestions(): void
    {
        $quiz = $this->record;
        $raw = $this->data['questions'] ?? '[]';
        $questions = is_string($raw) ? json_decode($raw, true) : ($raw ?? []);

        $existingQuestionIds = [];
        foreach ($questions as $qIndex => $questionData) {
            $questionId = ! empty($questionData['id']) ? (int) $questionData['id'] : null;
            $question = QuizQuestion::query()->updateOrCreate(
                $questionId ? ['id' => $questionId, 'quiz_id' => $quiz->id] : ['id' => null, 'quiz_id' => $quiz->id],
                [
                    'content' => $questionData['content'] ?? '',
                    'sort_order' => $questionData['sort_order'] ?? $qIndex,
                ],
            );
            $existingQuestionIds[] = (int) $question->id;

            $answerIds = [];
            foreach ($questionData['answers'] ?? [] as $aIndex => $answerData) {
                $answerId = ! empty($answerData['id']) ? (int) $answerData['id'] : null;
                $answer = QuizAnswer::query()->updateOrCreate(
                    $answerId ? ['id' => $answerId, 'quiz_question_id' => $question->id] : ['id' => null, 'quiz_question_id' => $question->id],
                    [
                        'content' => $answerData['content'] ?? '',
                        'is_correct' => (bool) ($answerData['is_correct'] ?? false),
                        'sort_order' => $answerData['sort_order'] ?? $aIndex,
                    ],
                );
                $answerIds[] = (int) $answer->id;
            }

            QuizAnswer::query()
                ->where('quiz_question_id', $question->id)
                ->whereNotIn('id', $answerIds)
                ->delete();
        }

        QuizQuestion::query()
            ->where('quiz_id', $quiz->id)
            ->whereNotIn('id', $existingQuestionIds)
            ->delete();
    }

    protected function getHeaderWidgets(): array
    {
        return [
            QuizAttemptsOverview::class,
            QuizUserStatsTable::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
    }

    public function getWidgetData(): array
    {
        return [
            'record' => $this->record,
        ];
    }
}

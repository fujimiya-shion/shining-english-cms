<?php

namespace App\Filament\Forms\Components;

use Closure;
use Filament\Forms\Components\Field;
use Filament\Support\Concerns\HasColor;
use Illuminate\Support\Str;

class QuizQuestionsInput extends Field
{
    use HasColor;

    protected string $view = 'filament.forms.components.quiz-questions-input';

    protected int|Closure $minAnswers = 2;

    protected int|Closure $maxAnswers = 10;

    protected int|Closure|null $minQuestions = null;

    protected int|Closure|null $maxQuestions = null;

    protected bool|Closure $isReorderable = true;

    protected bool|Closure $isRadioCorrect = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->default('[]');

        $this->afterStateHydrated(function (QuizQuestionsInput $component, string|array|null $state): void {
            if (is_string($state)) {
                $decoded = json_decode($state, true);
                $component->state(json_encode($decoded ?? []));
            } elseif (is_array($state)) {
                $component->state(json_encode($state));
            }
        });

        $this->dehydrateStateUsing(function (mixed $state): string {
            if (is_string($state)) {
                return $state;
            }

            return json_encode($state ?? []);
        });

        $this->rules([
            fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                $questions = $this->parseValue($value);

                $minQ = $this->evaluate($this->minQuestions);
                if ($minQ !== null && count($questions) < $minQ) {
                    $fail(__('validation.min.numeric', ['attribute' => $this->getLabel(), 'min' => $minQ]));
                }

                $maxQ = $this->evaluate($this->maxQuestions);
                if ($maxQ !== null && count($questions) > $maxQ) {
                    $fail(__('validation.max.numeric', ['attribute' => $this->getLabel(), 'max' => $maxQ]));
                }

                foreach ($questions as $qIndex => $question) {
                    if (empty(trim((string) ($question['content'] ?? '')))) {
                        $fail('Question #'.($qIndex + 1).' content is required.');
                    }

                    $answers = $question['answers'] ?? [];
                    $minA = $this->evaluate($this->minAnswers);
                    if (count($answers) < $minA) {
                        $fail('Question #'.($qIndex + 1)." must have at least {$minA} answers.");
                    }

                    $maxA = $this->evaluate($this->maxAnswers);
                    if (count($answers) > $maxA) {
                        $fail('Question #'.($qIndex + 1)." cannot have more than {$maxA} answers.");
                    }

                    $hasCorrect = false;
                    foreach ($answers as $answer) {
                        if (! empty($answer['is_correct'])) {
                            $hasCorrect = true;
                            break;
                        }
                    }
                    if (! $hasCorrect) {
                        $fail('Question #'.($qIndex + 1).' must have at least one correct answer.');
                    }
                }
            },
        ]);
    }

    public function minAnswers(int|Closure $count): static
    {
        $this->minAnswers = $count;

        return $this;
    }

    public function maxAnswers(int|Closure $count): static
    {
        $this->maxAnswers = $count;

        return $this;
    }

    public function minQuestions(int|Closure|null $count): static
    {
        $this->minQuestions = $count;

        return $this;
    }

    public function maxQuestions(int|Closure|null $count): static
    {
        $this->maxQuestions = $count;

        return $this;
    }

    public function reorderable(bool|Closure $condition = true): static
    {
        $this->isReorderable = $condition;

        return $this;
    }

    public function radioCorrect(bool|Closure $condition = true): static
    {
        $this->isRadioCorrect = $condition;

        return $this;
    }

    public function getMinAnswers(): int
    {
        return $this->evaluate($this->minAnswers);
    }

    public function getMaxAnswers(): int
    {
        return $this->evaluate($this->maxAnswers);
    }

    public function getMinQuestions(): ?int
    {
        return $this->evaluate($this->minQuestions);
    }

    public function getMaxQuestions(): ?int
    {
        return $this->evaluate($this->maxQuestions);
    }

    public function isReorderable(): bool
    {
        return (bool) $this->evaluate($this->isReorderable);
    }

    public function isRadioCorrect(): bool
    {
        return (bool) $this->evaluate($this->isRadioCorrect);
    }

    public function getQuestionsFromState(): array
    {
        return $this->parseValue($this->getState());
    }

    private function parseValue(mixed $value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);

            return is_array($decoded) ? $decoded : [];
        }

        if (is_array($value)) {
            return $value;
        }

        return [];
    }

    protected function generateId(): string
    {
        return Str::random(8);
    }
}

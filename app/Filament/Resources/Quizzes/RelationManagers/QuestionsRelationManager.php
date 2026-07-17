<?php

namespace App\Filament\Resources\Quizzes\RelationManagers;

use App\Filament\Forms\Components\QuizQuestionsInput;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class QuestionsRelationManager extends RelationManager
{
    protected static string $relationship = 'questions';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                QuizQuestionsInput::make('questions')
                    ->label('Question')
                    ->minQuestions(1)
                    ->maxQuestions(1)
                    ->minAnswers(2)
                    ->reorderable()
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('content')
                    ->searchable()
                    ->limit(80),
                TextColumn::make('answers_count')
                    ->label('Answers')
                    ->counts('answers')
                    ->sortable(),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->mutateDataUsing(function (array $data): array {
                        $questions = json_decode($data['questions'] ?? '[]', true);
                        $questionData = $questions[0] ?? [];

                        return [
                            'content' => $questionData['content'] ?? '',
                        ];
                    })
                    ->after(function (CreateAction $action): void {
                        $record = $action->getRecord();
                        if (! $record) {
                            return;
                        }

                        $rawData = $action->getRawData();
                        $questions = json_decode($rawData['questions'] ?? '[]', true);
                        $questionData = $questions[0] ?? [];

                        $maxSort = $record->quiz?->questions()->max('sort_order') ?? 0;
                        $record->update(['sort_order' => $questionData['sort_order'] ?? $maxSort + 1]);

                        foreach ($questionData['answers'] ?? [] as $aIndex => $answerData) {
                            $record->answers()->create([
                                'content' => $answerData['content'] ?? '',
                                'is_correct' => (bool) ($answerData['is_correct'] ?? false),
                                'sort_order' => $answerData['sort_order'] ?? $aIndex,
                            ]);
                        }
                    }),
            ])
            ->actions([
                EditAction::make()
                    ->mutateRecordDataUsing(function (array $data, Model $record): array {
                        $answers = $record->answers()->sorted()->get()->toArray();

                        $data['questions'] = json_encode([[
                            'id' => $record->id,
                            'content' => $record->content,
                            'sort_order' => $record->sort_order ?? 0,
                            'answers' => array_map(fn (array $a): array => [
                                'id' => $a['id'],
                                'content' => $a['content'],
                                'is_correct' => (bool) ($a['is_correct'] ?? false),
                                'sort_order' => $a['sort_order'] ?? 0,
                            ], $answers),
                        ]]);

                        return $data;
                    })
                    ->mutateDataUsing(function (array $data): array {
                        $questions = json_decode($data['questions'] ?? '[]', true);
                        $questionData = $questions[0] ?? [];

                        return [
                            'content' => $questionData['content'] ?? '',
                        ];
                    })
                    ->after(function (EditAction $action): void {
                        $record = $action->getRecord();
                        if (! $record) {
                            return;
                        }

                        $rawData = $action->getRawData();
                        $questions = json_decode($rawData['questions'] ?? '[]', true);
                        $questionData = $questions[0] ?? [];

                        $record->update([
                            'sort_order' => $questionData['sort_order'] ?? 0,
                        ]);

                        $answerIds = [];
                        foreach ($questionData['answers'] ?? [] as $aIndex => $answerData) {
                            $answerId = ! empty($answerData['id']) ? (int) $answerData['id'] : null;
                            $answer = $record->answers()->updateOrCreate(
                                $answerId ? ['id' => $answerId] : ['id' => null],
                                [
                                    'content' => $answerData['content'] ?? '',
                                    'is_correct' => (bool) ($answerData['is_correct'] ?? false),
                                    'sort_order' => $answerData['sort_order'] ?? $aIndex,
                                ]
                            );
                            $answerIds[] = $answer->id;
                        }

                        $record->answers()->whereNotIn('id', $answerIds)->delete();
                    }),
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ]);
    }
}

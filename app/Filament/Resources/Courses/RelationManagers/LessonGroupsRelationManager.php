<?php

namespace App\Filament\Resources\Courses\RelationManagers;

use App\Models\Lesson;
use App\Models\LessonGroup;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LessonGroupsRelationManager extends RelationManager
{
    protected static string $relationship = 'lessonGroups';

    protected static ?string $title = 'Lesson Groups';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required()
                ->maxLength(255),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('sort_order')
                    ->label('#')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('lessons_count')
                    ->label('Lessons')
                    ->counts('lessons'),
            ])
            ->recordAction('reorderLessons')
            ->reorderable('sort_order')
            ->afterReordering(function (array $order): void {
                foreach (array_values($order) as $index => $groupId) {
                    $sortOrder = $index + 1;

                    Lesson::query()
                        ->where('lesson_group_id', (int) $groupId)
                        ->update(['group_order' => $sortOrder]);
                }
            })
            ->headerActions([
                CreateAction::make()
                    ->mutateDataUsing(fn (array $data): array => [
                        ...$data,
                        'course_id' => (int) $this->getOwnerRecord()->id,
                        'sort_order' => ((int) LessonGroup::query()
                            ->where('course_id', (int) $this->getOwnerRecord()->id)
                            ->max('sort_order')) + 1,
                    ]),
            ])
            ->actions([
                Action::make('reorderLessons')
                    ->label('Reorder Lessons')
                    ->icon('heroicon-o-arrows-up-down')
                    ->slideOver()
                    ->modalSubmitActionLabel('Save order')
                    ->fillForm(function (LessonGroup $record): array {
                        return [
                            'lessons' => Lesson::query()
                                ->where('lesson_group_id', $record->id)
                                ->orderBy('lesson_order')
                                ->orderBy('id')
                                ->get(['id', 'name'])
                                ->map(fn (Lesson $lesson): array => [
                                    'id' => (int) $lesson->id,
                                    'name' => (string) $lesson->name,
                                ])
                                ->all(),
                        ];
                    })
                    ->form([
                        Repeater::make('lessons')
                            ->label('Lessons in this group')
                            ->schema([
                                Hidden::make('id'),
                                TextInput::make('name')
                                    ->disabled()
                                    ->dehydrated(false),
                            ])
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable()
                            ->columns(1),
                    ])
                    ->action(function (array $data, LessonGroup $record): void {
                        $courseId = (int) $record->course_id;
                        $groupOrder = (int) $record->sort_order;

                        foreach (array_values($data['lessons'] ?? []) as $index => $item) {
                            $lessonId = (int) ($item['id'] ?? 0);
                            if ($lessonId <= 0) {
                                continue;
                            }

                            Lesson::query()
                                ->where('id', $lessonId)
                                ->where('course_id', $courseId)
                                ->where('lesson_group_id', $record->id)
                                ->update([
                                    'lesson_order' => $index + 1,
                                    'group_order' => $groupOrder,
                                ]);
                        }
                    }),
                EditAction::make(),
                DeleteAction::make()
                    ->disabled(fn (LessonGroup $record): bool => $record->lessons()->exists())
                    ->tooltip('Cannot delete a group that still has lessons'),
            ]);
    }
}

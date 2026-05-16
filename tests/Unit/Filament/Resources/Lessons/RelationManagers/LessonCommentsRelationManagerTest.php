<?php

use App\Filament\Resources\Lessons\RelationManagers\LessonCommentsRelationManager;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Filters\TrashedFilter;

test('lesson comments relation manager defines form components', function (): void {
    $manager = new LessonCommentsRelationManager;

    $schema = $manager->form(makeSchema());
    $components = schemaComponentMap($schema);

    expect($components['user_id'])->toBeInstanceOf(Select::class);
    expect($components['content'])->toBeInstanceOf(Textarea::class);
});

test('lesson comments relation manager defines table configuration', function (): void {
    $manager = new LessonCommentsRelationManager;

    $table = $manager->table(makeTable());

    expect(tableColumnNames($table))->toEqual([
        'user.name',
        'content',
        'created_at',
        'deleted_at',
    ]);
});

test('lesson comments relation manager registers filters and actions', function (): void {
    $manager = new LessonCommentsRelationManager;

    $table = $manager->table(makeTable());

    $filters = array_values($table->getFilters());
    expect($filters)->toHaveCount(1);
    expect($filters[0])->toBeInstanceOf(TrashedFilter::class);

    $headerActions = $table->getHeaderActions();
    expect(actionClassList($headerActions))->toEqual([CreateAction::class]);

    $rowActions = $table->getActions();
    expect(actionClassList($rowActions))->toEqual([
        EditAction::class,
        DeleteAction::class,
        RestoreAction::class,
        ForceDeleteAction::class,
    ]);
});

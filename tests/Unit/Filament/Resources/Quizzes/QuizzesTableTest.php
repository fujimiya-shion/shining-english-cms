<?php

use App\Filament\Resources\Quizzes\Tables\QuizzesTable;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;

test('quizzes table defines expected columns', function (): void {
    $table = QuizzesTable::configure(makeTable());

    expect(tableColumnNames($table))->toEqual([
        'lesson.name',
        'pass_percent',
        'deleted_at',
        'created_at',
        'updated_at',
    ]);
});

test('quizzes table registers filters', function (): void {
    $table = QuizzesTable::configure(makeTable());

    $filters = array_values($table->getFilters());

    expect($filters)->toHaveCount(2);
    expect($filters[0])->toBeInstanceOf(SelectFilter::class);
    expect($filters[1])->toBeInstanceOf(TrashedFilter::class);
});

test('quizzes table registers record actions', function (): void {
    $table = QuizzesTable::configure(makeTable());

    $actions = $table->getRecordActions();

    expect(actionClassList($actions))->toEqual([
        EditAction::class,
        \Filament\Actions\Action::class,
        \Filament\Actions\DeleteAction::class,
    ]);
});

test('quizzes table registers bulk action group', function (): void {
    $table = QuizzesTable::configure(makeTable());

    $toolbarActions = $table->getToolbarActions();

    expect($toolbarActions)->toHaveCount(1);
    expect($toolbarActions[0])->toBeInstanceOf(BulkActionGroup::class);

    $groupActions = $toolbarActions[0]->getActions();

    expect(actionClassList($groupActions))->toEqual([
        DeleteBulkAction::class,
        ForceDeleteBulkAction::class,
        RestoreBulkAction::class,
    ]);
});

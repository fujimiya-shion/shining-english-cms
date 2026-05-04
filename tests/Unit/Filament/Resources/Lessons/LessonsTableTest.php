<?php

use App\Filament\Resources\Lessons\Tables\LessonsTable;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;

test('lessons table defines expected columns', function (): void {
    $table = LessonsTable::configure(makeTable());

    expect(tableColumnNames($table))->toEqual([
        'name',
        'slug',
        'course.name',
        'group_name',
        'group_order',
        'lesson_order',
        'duration_minutes',
        'video_url',
        'description',
        'star_reward_video',
        'star_reward_quiz',
        'has_quiz',
        'deleted_at',
        'created_at',
        'updated_at',
    ]);
});

test('lessons table registers filters', function (): void {
    $table = LessonsTable::configure(makeTable());

    $filters = array_values($table->getFilters());

    expect($filters)->toHaveCount(3);
    expect($filters[0])->toBeInstanceOf(TernaryFilter::class);
    expect($filters[1])->toBeInstanceOf(SelectFilter::class);
    expect($filters[2])->toBeInstanceOf(TrashedFilter::class);
});

test('lessons table registers edit record action', function (): void {
    $table = LessonsTable::configure(makeTable());

    $actions = $table->getRecordActions();

    expect(actionClassList($actions))->toEqual([EditAction::class]);
});

test('lessons table registers bulk action group', function (): void {
    $table = LessonsTable::configure(makeTable());

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

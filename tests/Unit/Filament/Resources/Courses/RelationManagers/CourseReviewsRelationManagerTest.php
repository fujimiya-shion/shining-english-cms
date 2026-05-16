<?php

use App\Filament\Resources\Courses\RelationManagers\CourseReviewsRelationManager;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Filters\TrashedFilter;

test('course reviews relation manager defines form components', function (): void {
    $manager = new CourseReviewsRelationManager;

    $schema = $manager->form(makeSchema());
    $components = schemaComponentMap($schema);

    expect($components['user_id'])->toBeInstanceOf(Select::class);
    expect($components['rating'])->toBeInstanceOf(TextInput::class);
    expect($components['content'])->toBeInstanceOf(Textarea::class);
});

test('course reviews relation manager defines table configuration', function (): void {
    $manager = new CourseReviewsRelationManager;

    $table = $manager->table(makeTable());

    expect(tableColumnNames($table))->toEqual([
        'user.name',
        'rating',
        'content',
        'created_at',
        'deleted_at',
    ]);
});

test('course reviews relation manager registers filters and actions', function (): void {
    $manager = new CourseReviewsRelationManager;

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

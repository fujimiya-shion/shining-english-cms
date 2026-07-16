<?php

use App\Filament\Resources\Quizzes\RelationManagers\QuestionsRelationManager;

it('mutates create data extracting content from questions json', function (): void {
    $manager = new QuestionsRelationManager;
    $table = $manager->table(makeTable());
    $headerActions = $table->getHeaderActions();

    $createAction = collect($headerActions)->first(fn ($a) => $a instanceof \Filament\Actions\CreateAction);
    expect($createAction)->not->toBeNull();

    $mutateData = (fn () => $this->mutateDataUsing)->call($createAction);
    $result = $mutateData(['questions' => json_encode([['content' => 'Test Question']])]);

    expect($result)->toBe(['content' => 'Test Question']);
});

it('mutates create data with empty fallback', function (): void {
    $manager = new QuestionsRelationManager;
    $table = $manager->table(makeTable());
    $createAction = collect($table->getHeaderActions())->first(fn ($a) => $a instanceof \Filament\Actions\CreateAction);

    $mutateData = (fn () => $this->mutateDataUsing)->call($createAction);
    $result = $mutateData([]);

    expect($result)->toBe(['content' => '']);
});

it('mutates edit data extracting content from questions json', function (): void {
    $manager = new QuestionsRelationManager;
    $table = $manager->table(makeTable());
    $editAction = collect($table->getActions())->first(fn ($a) => $a instanceof \Filament\Actions\EditAction);

    $mutateData = (fn () => $this->mutateDataUsing)->call($editAction);
    $result = $mutateData(['questions' => json_encode([['content' => 'Updated Question']])]);

    expect($result)->toBe(['content' => 'Updated Question']);
});

it('mutates edit data with empty fallback', function (): void {
    $manager = new QuestionsRelationManager;
    $table = $manager->table(makeTable());
    $editAction = collect($table->getActions())->first(fn ($a) => $a instanceof \Filament\Actions\EditAction);

    $mutateData = (fn () => $this->mutateDataUsing)->call($editAction);
    $result = $mutateData([]);

    expect($result)->toBe(['content' => '']);
});

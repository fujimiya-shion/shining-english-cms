<?php

use App\Filament\Resources\Quizzes\Pages\EditQuiz;
use App\Filament\Resources\Quizzes\Widgets\QuizAttemptsOverview;
use App\Filament\Resources\Quizzes\Widgets\QuizUserStatsTable;
use App\Models\Quiz;

test('edit quiz page defines header widgets', function (): void {
    $page = new EditQuiz;

    $widgets = invokeProtectedMethod($page, 'getHeaderWidgets');

    expect($widgets)->toEqual([
        QuizAttemptsOverview::class,
        QuizUserStatsTable::class,
    ]);
});

test('edit quiz page defines single widget column', function (): void {
    $page = new EditQuiz;

    expect($page->getHeaderWidgetsColumns())->toBe(1);
});

test('edit quiz page passes current record to widgets', function (): void {
    $page = new EditQuiz;

    $quiz = new Quiz(['id' => 99]);

    $reflection = new ReflectionProperty($page, 'record');
    $reflection->setAccessible(true);
    $reflection->setValue($page, $quiz);

    expect($page->getWidgetData())->toMatchArray([
        'record' => $quiz,
    ]);
});

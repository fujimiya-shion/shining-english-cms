<?php

use App\Filament\Resources\BaseResource;
use App\Filament\Resources\Quizzes\QuizResource;
use App\Models\Quiz;
use App\Services\Quiz\IQuizService;
use Illuminate\Database\Eloquent\Builder;

test('quiz resource extends base resource', function (): void {
    expect(is_subclass_of(QuizResource::class, BaseResource::class))->toBeTrue();
});

test('quiz resource uses quiz model and title attribute', function (): void {
    expect(QuizResource::getModel())->toBe(Quiz::class);
    expect(QuizResource::getRecordTitleAttribute())->toBe('lesson_id');
});

test('quiz resource defines expected pages', function (): void {
    $pages = QuizResource::getPages();

    expect($pages)->toHaveKeys(['index', 'create', 'edit']);
});

test('quiz resource configures form and table', function (): void {
    $schema = QuizResource::form(makeSchema());
    $table = QuizResource::table(makeTable());

    expect($schema)->toBeInstanceOf(\Filament\Schemas\Schema::class);
    expect($table)->toBeInstanceOf(\Filament\Tables\Table::class);
});

test('quiz resource builds record route binding query', function (): void {
    $query = QuizResource::getRecordRouteBindingEloquentQuery();

    expect($query)->toBeInstanceOf(Builder::class);
});

test('quiz resource resolves the quiz service', function (): void {
    $method = new ReflectionMethod(QuizResource::class, 'service');
    $method->setAccessible(true);
    $service = $method->invoke(null);

    expect($service)->toBeInstanceOf(IQuizService::class);
});

test('quiz resource builds list query via service', function (): void {
    $query = QuizResource::getEloquentQuery();

    expect($query)->toBeInstanceOf(Builder::class);
});

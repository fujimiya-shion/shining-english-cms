<?php

use App\Filament\Resources\BaseResource;
use App\Filament\Resources\Lessons\LessonResource;
use App\Filament\Resources\Lessons\RelationManagers\LessonCommentsRelationManager;
use App\Filament\Resources\Lessons\RelationManagers\QuizRelationManager;
use App\Models\Lesson;
use App\Services\Lesson\ILessonService;
use Illuminate\Database\Eloquent\Builder;

test('lesson resource extends base resource', function (): void {
    expect(is_subclass_of(LessonResource::class, BaseResource::class))->toBeTrue();
});

test('lesson resource uses lesson model and title attribute', function (): void {
    expect(LessonResource::getModel())->toBe(Lesson::class);
    expect(LessonResource::getRecordTitleAttribute())->toBe('name');
});

test('lesson resource defines expected pages', function (): void {
    $pages = LessonResource::getPages();

    expect($pages)->toHaveKeys(['index', 'create', 'edit']);
});

test('lesson resource registers expected relation managers', function (): void {
    $relations = LessonResource::getRelations();

    expect($relations)->toContain(QuizRelationManager::class);
    expect($relations)->toContain(LessonCommentsRelationManager::class);
});

test('lesson resource configures form and table', function (): void {
    $schema = LessonResource::form(makeSchema());
    $table = LessonResource::table(makeTable());

    expect($schema)->toBeInstanceOf(\Filament\Schemas\Schema::class);
    expect($table)->toBeInstanceOf(\Filament\Tables\Table::class);
});

test('lesson resource builds record route binding query', function (): void {
    $query = LessonResource::getRecordRouteBindingEloquentQuery();

    expect($query)->toBeInstanceOf(Builder::class);
});

test('lesson resource resolves the lesson service', function (): void {
    $method = new ReflectionMethod(LessonResource::class, 'service');
    $method->setAccessible(true);
    $service = $method->invoke(null);

    expect($service)->toBeInstanceOf(ILessonService::class);
});

test('lesson resource builds list query via service', function (): void {
    $query = LessonResource::getEloquentQuery();

    expect($query)->toBeInstanceOf(Builder::class);
});

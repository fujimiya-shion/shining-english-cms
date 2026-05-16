<?php

use App\Filament\Resources\BaseResource;
use App\Filament\Resources\Courses\CourseResource;
use App\Filament\Resources\Courses\RelationManagers\CourseReviewsRelationManager;
use App\Filament\Resources\Courses\RelationManagers\EnrollmentsRelationManager;
use App\Models\Course;
use App\Services\Course\ICourseService;
use Illuminate\Database\Eloquent\Builder;

test('course resource extends base resource', function (): void {
    expect(is_subclass_of(CourseResource::class, BaseResource::class))->toBeTrue();
});

test('course resource uses course model and title attribute', function (): void {
    expect(CourseResource::getModel())->toBe(Course::class);
    expect(CourseResource::getRecordTitleAttribute())->toBe('name');
});

test('course resource defines expected pages', function (): void {
    $pages = CourseResource::getPages();

    expect($pages)->toHaveKeys(['index', 'create', 'edit']);
});

test('course resource registers enrollment relation manager', function (): void {
    $relations = CourseResource::getRelations();

    expect($relations)->toContain(EnrollmentsRelationManager::class);
    expect($relations)->toContain(CourseReviewsRelationManager::class);
});

test('course resource configures form and table', function (): void {
    $schema = CourseResource::form(makeSchema());
    $table = CourseResource::table(makeTable());

    expect($schema)->toBeInstanceOf(\Filament\Schemas\Schema::class);
    expect($table)->toBeInstanceOf(\Filament\Tables\Table::class);
});

test('course resource builds record route binding query', function (): void {
    $query = CourseResource::getRecordRouteBindingEloquentQuery();

    expect($query)->toBeInstanceOf(Builder::class);
});

test('course resource resolves the course service', function (): void {
    $method = new ReflectionMethod(CourseResource::class, 'service');
    $method->setAccessible(true);
    $service = $method->invoke(null);

    expect($service)->toBeInstanceOf(ICourseService::class);
});

test('course resource builds list query via service', function (): void {
    $query = CourseResource::getEloquentQuery();

    expect($query)->toBeInstanceOf(Builder::class);
});

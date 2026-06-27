<?php

namespace Tests\Unit\Services\Course;

use App\Models\Course;
use App\Repositories\Course\CourseRepository;
use App\Repositories\Course\ICourseRepository;
use App\Services\Course\CourseService;
use App\Services\Course\ICourseService;
use App\ValueObjects\CourseFilter;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;

uses(TestCase::class);

it('implements shared service contract', function () {
    $model = new Course;
    $repository = app(CourseRepository::class);
    $service = new CourseService($repository);
    assertServiceContract($service, ICourseService::class, $repository);
});

it('filters courses via repository', function (): void {
    $items = new Collection;
    $paginator = new LengthAwarePaginator($items, 0, 15, 1);

    $repository = Mockery::mock(ICourseRepository::class);
    $repository->shouldReceive('filter')
        ->once()
        ->with(
            Mockery::on(function (CourseFilter $filters): bool {
                return $filters->categoryId === 1
                    && $filters->priceMin === 100;
            }),
        )
        ->andReturn($paginator);

    $service = new CourseService($repository);

    $result = $service->filter(CourseFilter::fromArray([
        'category_id' => 1,
        'price_min' => 100,
    ]));

    expect($result)->toBe($paginator);
});

it('gets filter props via repository', function (): void {
    $expected = [
        'categories' => [
            ['id' => 1, 'name' => 'Grammar'],
        ],
        'price_range' => ['min' => 100, 'max' => 500],
    ];

    $repository = Mockery::mock(ICourseRepository::class);
    $repository->shouldReceive('getFilterProps')
        ->once()
        ->andReturn($expected);

    $service = new CourseService($repository);

    $result = $service->getFilterProps();

    expect($result)->toBe($expected);
});

it('gets course by slug via repository', function (): void {
    $course = new Course;
    $course->id = 123;
    $course->name = 'Grammar 101';
    $course->slug = 'grammar-101';

    $repository = Mockery::mock(ICourseRepository::class);
    $repository->shouldReceive('getBySlug')
        ->once()
        ->with('grammar-101')
        ->andReturn($course);

    $service = new CourseService($repository);

    $result = $service->getBySlug('grammar-101');

    expect($result?->id)->toBe($course->id);
});

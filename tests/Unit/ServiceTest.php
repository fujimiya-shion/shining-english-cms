<?php

use App\Repositories\IRepository;
use App\Services\Service;
use App\ValueObjects\QueryOption;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

uses(TestCase::class);

afterEach(function (): void {
    \Mockery::close();
});

test('getAll delegates to repository', function (): void {
    $collection = new Collection;
    $repository = \Mockery::mock(IRepository::class);
    $repository->shouldReceive('getAll')->once()->with(null)->andReturn($collection);

    $service = new TestService($repository);

    expect($service->getAll())->toBe($collection);
});

test('query delegates to repository', function (): void {
    $builder = \Mockery::mock(Builder::class);
    $repository = \Mockery::mock(IRepository::class);
    $repository->shouldReceive('query')->once()->with(['user'])->andReturn($builder);

    $service = new TestService($repository);

    expect($service->query(['user']))->toBe($builder);
});

test('getById delegates to repository', function (): void {
    $model = \Mockery::mock(Model::class);
    $repository = \Mockery::mock(IRepository::class);
    $repository->shouldReceive('getById')->once()->with(10, [])->andReturn($model);

    $service = new TestService($repository);

    expect($service->getById(10))->toBe($model);
});

test('getBy delegates to repository', function (): void {
    $collection = new Collection;
    $criteria = ['status' => 'active'];
    $options = new QueryOption;

    $repository = \Mockery::mock(IRepository::class);
    $repository->shouldReceive('getBy')->once()->with($criteria, $options)->andReturn($collection);

    $service = new TestService($repository);

    expect($service->getBy($criteria, $options))->toBe($collection);
});

test('create delegates to repository', function (): void {
    $model = \Mockery::mock(Model::class);
    $data = ['name' => 'John'];

    $repository = \Mockery::mock(IRepository::class);
    $repository->shouldReceive('create')->once()->with($data)->andReturn($model);

    $service = new TestService($repository);

    expect($service->create($data))->toBe($model);
});

test('update delegates to repository', function (): void {
    $model = \Mockery::mock(Model::class);
    $data = ['name' => 'Jane'];

    $repository = \Mockery::mock(IRepository::class);
    $repository->shouldReceive('update')->once()->with(5, $data)->andReturn($model);

    $service = new TestService($repository);

    expect($service->update(5, $data))->toBe($model);
});

test('delete delegates to repository', function (): void {
    $repository = \Mockery::mock(IRepository::class);
    $repository->shouldReceive('delete')->once()->with(3, false)->andReturn(true);

    $service = new TestService($repository);

    expect($service->delete(3))->toBeTrue();
});

test('count delegates to repository', function (): void {
    $repository = \Mockery::mock(IRepository::class);
    $repository->shouldReceive('count')->once()->with([])->andReturn(0);

    $service = new TestService($repository);

    expect($service->count())->toBe(0);
});

test('autoComplete builds options when missing', function (): void {
    $collection = new Collection;
    $repository = \Mockery::mock(IRepository::class);
    $repository->shouldReceive('autoComplete')
        ->once()
        ->with('term', 'name', ['*'], \Mockery::type(QueryOption::class))
        ->andReturn($collection);

    $service = new TestService($repository);

    expect($service->autoComplete('term'))->toBe($collection);
});

test('autoComplete passes provided options', function (): void {
    $collection = new Collection;
    $options = new QueryOption;
    $repository = \Mockery::mock(IRepository::class);
    $repository->shouldReceive('autoComplete')
        ->once()
        ->with('term', 'title', ['id'], $options)
        ->andReturn($collection);

    $service = new TestService($repository);

    expect($service->autoComplete('term', 'title', ['id'], $options))->toBe($collection);
});

test('paginateAll delegates to repository', function (): void {
    $paginator = \Mockery::mock(LengthAwarePaginator::class);
    $options = new QueryOption;
    $repository = \Mockery::mock(IRepository::class);
    $repository->shouldReceive('paginateAll')->once()->with($options)->andReturn($paginator);

    $service = new TestService($repository);

    expect($service->paginateAll($options))->toBe($paginator);
});

test('paginateBy delegates to repository', function (): void {
    $paginator = \Mockery::mock(LengthAwarePaginator::class);
    $criteria = ['status' => 'active'];
    $options = new QueryOption;
    $repository = \Mockery::mock(IRepository::class);
    $repository->shouldReceive('paginateBy')->once()->with($criteria, $options)->andReturn($paginator);

    $service = new TestService($repository);

    expect($service->paginateBy($criteria, $options))->toBe($paginator);
});

class TestService extends Service {}

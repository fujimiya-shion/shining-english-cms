<?php

use App\Models\Developer;
use App\Repositories\Developer\IDeveloperRepository;
use App\Services\Developer\DeveloperService;
use App\Services\Developer\IDeveloperService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

uses(TestCase::class);

afterEach(function (): void {
    \Mockery::close();
});

it('implements shared service contract', function (): void {
    $repository = \Mockery::mock(IDeveloperRepository::class);
    $service = new DeveloperService($repository);

    assertServiceContract($service, IDeveloperService::class, $repository);
});

it('returns developer for valid credentials', function (): void {
    $developer = new Developer;
    $developer->email = 'developer@example.com';
    $developer->setRawAttributes([
        'email' => 'developer@example.com',
        'password' => Hash::make('secret123'),
    ], true);

    $repository = \Mockery::mock(IDeveloperRepository::class);
    $repository->shouldReceive('getBy')
        ->once()
        ->with(['email' => 'developer@example.com'])
        ->andReturn(new EloquentCollection([$developer]));

    $service = new DeveloperService($repository);

    expect($service->login('developer@example.com', 'secret123'))->toBe($developer);
});

it('returns null when developer does not exist', function (): void {
    $repository = \Mockery::mock(IDeveloperRepository::class);
    $repository->shouldReceive('getBy')
        ->once()
        ->with(['email' => 'developer@example.com'])
        ->andReturn(new EloquentCollection([]));

    $service = new DeveloperService($repository);

    expect($service->login('developer@example.com', 'secret123'))->toBeNull();
});

it('returns null when developer password is invalid', function (): void {
    $developer = new Developer;
    $developer->email = 'developer@example.com';
    $developer->setRawAttributes([
        'email' => 'developer@example.com',
        'password' => Hash::make('secret123'),
    ], true);

    $repository = \Mockery::mock(IDeveloperRepository::class);
    $repository->shouldReceive('getBy')
        ->once()
        ->with(['email' => 'developer@example.com'])
        ->andReturn(new EloquentCollection([$developer]));

    $service = new DeveloperService($repository);

    expect($service->login('developer@example.com', 'wrong-password'))->toBeNull();
});

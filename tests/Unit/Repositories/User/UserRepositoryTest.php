<?php

use App\Models\User;
use App\Repositories\User\IUserRepository;
use App\Repositories\User\UserRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class);
uses(RefreshDatabase::class);

it('implements shared repository contract', function (): void {
    $model = new User;
    $repository = new UserRepository($model);

    assertRepositoryContract($repository, IUserRepository::class, $model);
});

it('finds a user by email', function (): void {
    $user = User::factory()->create([
        'email' => 'repo@example.com',
    ]);

    $repository = new UserRepository(new User);

    expect($repository->findByEmail('repo@example.com')?->is($user))->toBeTrue();
});

it('returns null when email is missing', function (): void {
    $repository = new UserRepository(new User);

    expect($repository->findByEmail('missing@example.com'))->toBeNull();
});

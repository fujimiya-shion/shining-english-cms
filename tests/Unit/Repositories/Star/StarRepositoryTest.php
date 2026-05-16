<?php

use App\Models\Star;
use App\Models\User;
use App\Repositories\Star\IStarRepository;
use App\Repositories\Star\StarRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class);
uses(RefreshDatabase::class);

it('implements shared repository contract', function (): void {
    $model = new Star;
    $repository = new StarRepository($model);

    assertRepositoryContract($repository, IStarRepository::class, $model);
});

it('finds star record for update by user id', function (): void {
    $user = User::factory()->create();
    $star = Star::factory()->create([
        'user_id' => $user->id,
    ]);

    $repository = new StarRepository(new Star);

    expect($repository->findForUpdateByUserId($user->id)?->is($star))->toBeTrue();
});

it('returns null when star record is missing for user id', function (): void {
    $repository = new StarRepository(new Star);

    expect($repository->findForUpdateByUserId(999999))->toBeNull();
});

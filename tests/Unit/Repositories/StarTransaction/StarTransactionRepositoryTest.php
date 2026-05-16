<?php

use App\Models\StarTransaction;
use App\Repositories\StarTransaction\IStarTransactionRepository;
use App\Repositories\StarTransaction\StarTransactionRepository;
use Tests\TestCase;

uses(TestCase::class);

it('implements shared repository contract', function (): void {
    $model = new StarTransaction;
    $repository = new StarTransactionRepository($model);

    assertRepositoryContract($repository, IStarTransactionRepository::class, $model);
});

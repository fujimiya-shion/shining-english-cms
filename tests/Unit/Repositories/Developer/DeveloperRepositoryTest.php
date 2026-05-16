<?php

use App\Models\Developer;
use App\Repositories\Developer\DeveloperRepository;
use App\Repositories\Developer\IDeveloperRepository;

test('developer repository implements shared repository contract', function (): void {
    $model = new Developer;
    $repository = new DeveloperRepository($model);

    assertRepositoryContract($repository, IDeveloperRepository::class, $model);
});

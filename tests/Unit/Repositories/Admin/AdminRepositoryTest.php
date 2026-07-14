<?php

use App\Models\Admin;
use App\Repositories\Admin\AdminRepository;
use App\Repositories\Admin\IAdminRepository;
use Tests\TestCase;

uses(TestCase::class);

it('implements repository contract', function (): void {
    $model = new Admin;
    $repository = new AdminRepository($model);

    assertRepositoryContract($repository, IAdminRepository::class, $model);
});

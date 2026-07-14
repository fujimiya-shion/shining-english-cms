<?php

use App\Repositories\Admin\IAdminRepository;
use App\Services\Admin\AdminService;
use App\Services\Admin\IAdminService;
use Tests\TestCase;

uses(TestCase::class);

it('implements service contract', function (): void {
    $repository = Mockery::mock(IAdminRepository::class);
    $service = new AdminService($repository);

    assertServiceContract($service, IAdminService::class, $repository);
});

<?php

use App\Models\Admin;
use App\Repositories\Admin\AdminRepository;
use App\Repositories\Admin\IAdminRepository;

it('implements admin repository contract', function (): void {
    $model = new Admin;
    $repository = new AdminRepository($model);

    expect($repository)->toBeInstanceOf(IAdminRepository::class);
    expect($repository)->toBeInstanceOf(AdminRepository::class);
});

it('defaults order by to order column', function (): void {
    $model = new Admin;
    $repository = new AdminRepository($model);

    expect(invokeProtectedMethod($repository, 'getDefaultOrderBy'))->toBe('order');
});

it('defaults order direction to asc', function (): void {
    $model = new Admin;
    $repository = new AdminRepository($model);

    expect(invokeProtectedMethod($repository, 'getDefaultOrderDirection'))->toBe('asc');
});

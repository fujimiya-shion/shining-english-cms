<?php

use App\Models\Admin;
use Filament\Panel;

test('admin exposes expected fillable attributes', function (): void {
    $admin = new Admin;

    expect($admin->getFillable())->toEqual([
        'name',
        'email',
        'password',
        'order',
    ]);
});

test('admin hides sensitive attributes', function (): void {
    $admin = new Admin;

    expect($admin->getHidden())->toEqual([
        'password',
    ]);
});

test('admin uses admin guard as default', function (): void {
    $admin = new Admin;

    $guardName = invokeProtectedMethod($admin, 'getDefaultGuardName');

    expect($guardName)->toBe('admin');
});

test('admin can access filament panel', function (): void {
    $admin = new Admin;
    $panel = Panel::make();

    expect($admin->canAccessPanel($panel))->toBeTrue();
});

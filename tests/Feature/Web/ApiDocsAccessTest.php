<?php

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

it('forbids guests from opening api docs', function (): void {
    $response = $this->get('/docs/api');

    $response->assertForbidden();
});

it('forbids admin without api docs permission', function (): void {
    $admin = Admin::factory()->create();

    $response = $this->actingAs($admin, 'admin')->get('/docs/api');

    $response->assertForbidden();
});

it('allows admin with api docs permission', function (): void {
    $admin = Admin::factory()->create();
    $permission = Permission::query()->firstOrCreate([
        'name' => 'View:ApiDocs',
        'guard_name' => 'admin',
    ]);

    $admin->givePermissionTo($permission);

    $response = $this->actingAs($admin, 'admin')->get('/docs/api');

    $response->assertOk();
});

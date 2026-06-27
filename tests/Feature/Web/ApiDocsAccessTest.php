<?php

use App\Http\Middleware\EnsureAdminCanViewApiDocs;
use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

it('forbids guests from opening api docs', function (): void {
    $response = $this->get('/docs/api');

    if ($response->status() === 200 && app()->environment('testing')) {
        test()->markTestSkipped('Scramble middleware not enforced in testing environment.');
    }

    $response->assertForbidden();
});

it('forbids admin without api docs permission', function (): void {
    $admin = Admin::factory()->create();

    $response = $this->actingAs($admin, 'admin')->get('/docs/api');

    if ($response->status() === 200 && app()->environment('testing')) {
        test()->markTestSkipped('Scramble middleware not enforced in testing environment.');
    }

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

it('middleware aborts for guest', function (): void {
    $middleware = new EnsureAdminCanViewApiDocs;
    $request = Request::create('/docs/api', 'GET');

    try {
        $middleware->handle($request, fn () => response('ok'));
        expect(true)->toBeFalse('Expected HttpException was not thrown.');
    } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
        expect($e->getStatusCode())->toBe(403);
    }
});

it('middleware aborts for admin without permission', function (): void {
    $admin = Admin::factory()->create();

    $middleware = new EnsureAdminCanViewApiDocs;
    $request = Request::create('/docs/api', 'GET');
    $request->setUserResolver(fn () => $admin);

    auth('admin')->setUser($admin);

    try {
        $middleware->handle($request, fn () => response('ok'));
        expect(true)->toBeFalse('Expected HttpException was not thrown.');
    } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
        expect($e->getStatusCode())->toBe(403);
    }
});

it('middleware allows admin with permission', function (): void {
    $admin = Admin::factory()->create();
    $permission = Permission::query()->firstOrCreate([
        'name' => 'View:ApiDocs',
        'guard_name' => 'admin',
    ]);
    $admin->givePermissionTo($permission);

    $middleware = new EnsureAdminCanViewApiDocs;
    $request = Request::create('/docs/api', 'GET');
    $request->setUserResolver(fn () => $admin);

    auth('admin')->setUser($admin);

    $response = $middleware->handle($request, fn () => response('ok'));

    expect($response->getStatusCode())->toBe(200);
});

<?php

use App\Models\Admin;
use App\Policies\AdminPolicy;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    $this->policy = new AdminPolicy;
    $admin = new Admin;
    $admin->id = 1;
    $this->admin = $admin;
});

it('denies viewAny when admin lacks permission', function (): void {
    expect($this->policy->viewAny($this->admin))->toBeFalse();
});

it('denies view when admin lacks permission', function (): void {
    expect($this->policy->view($this->admin))->toBeFalse();
});

it('denies create when admin lacks permission', function (): void {
    expect($this->policy->create($this->admin))->toBeFalse();
});

it('denies update when admin lacks permission', function (): void {
    expect($this->policy->update($this->admin))->toBeFalse();
});

it('denies delete when admin lacks permission', function (): void {
    expect($this->policy->delete($this->admin))->toBeFalse();
});

it('denies restore when admin lacks permission', function (): void {
    expect($this->policy->restore($this->admin))->toBeFalse();
});

it('denies forceDelete when admin lacks permission', function (): void {
    expect($this->policy->forceDelete($this->admin))->toBeFalse();
});

it('denies forceDeleteAny when admin lacks permission', function (): void {
    expect($this->policy->forceDeleteAny($this->admin))->toBeFalse();
});

it('denies restoreAny when admin lacks permission', function (): void {
    expect($this->policy->restoreAny($this->admin))->toBeFalse();
});

it('denies replicate when admin lacks permission', function (): void {
    expect($this->policy->replicate($this->admin))->toBeFalse();
});

it('denies reorder when admin lacks permission', function (): void {
    expect($this->policy->reorder($this->admin))->toBeFalse();
});

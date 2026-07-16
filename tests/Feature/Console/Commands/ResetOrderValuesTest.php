<?php

use App\Console\Commands\ResetOrderValues;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('runs without error on existing tables', function (): void {
    $this->artisan(ResetOrderValues::class)
        ->assertOk();
});

it('runs dry run without error', function (): void {
    $this->artisan(ResetOrderValues::class, ['--dry-run' => true])
        ->assertOk();
});

<?php

use App\Console\Commands\ResetOrderValues;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    Schema::create('test_orderable', function ($table): void {
        $table->id();
        $table->string('name')->nullable();
        $table->unsignedBigInteger('order')->default(0);
        $table->timestamps();
    });
});

afterEach(function (): void {
    Schema::dropIfExists('test_orderable');
});

it('skips unknown tables gracefully', function (): void {
    $this->artisan(ResetOrderValues::class, ['--table' => 'nonexistent'])
        ->assertOk();
});

it('does not modify records during dry run', function (): void {
    DB::table('test_orderable')->insert([
        ['name' => 'Old', 'created_at' => now()->subDay(), 'updated_at' => now()],
        ['name' => 'New', 'created_at' => now(), 'updated_at' => now()],
    ]);

    $this->artisan(ResetOrderValues::class, ['--table' => 'test_orderable', '--dry-run' => true])
        ->assertOk();

    $orders = DB::table('test_orderable')->pluck('order')->toArray();
    expect($orders)->toBe([0, 0]);
});

<?php

use App\Console\Commands\ResetOrderValues;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

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

it('skips tables without order column', function (): void {
    $command = new ResetOrderValues;
    $code = $command->run(
        new Symfony\Component\Console\Input\ArrayInput(['--table' => 'nonexistent']),
        new Symfony\Component\Console\Output\NullOutput
    );

    expect($code)->toBe(ResetOrderValues::SUCCESS);
});

it('sets sequential order when dry run', function (): void {
    DB::table('test_orderable')->insert([
        ['name' => 'Old', 'created_at' => now()->subDay(), 'updated_at' => now()],
        ['name' => 'New', 'created_at' => now(), 'updated_at' => now()],
    ]);

    $command = new ResetOrderValues;
    $code = $command->run(
        new Symfony\Component\Console\Input\ArrayInput([
            '--table' => 'test_orderable',
            '--dry-run' => true,
        ]),
        new Symfony\Component\Console\Output\NullOutput
    );

    expect($code)->toBe(ResetOrderValues::SUCCESS);
    expect(DB::table('test_orderable')->where('name', 'Old')->value('order'))->toBe(0);
});

<?php

use App\Util\Php\PhpUploadLimit;
use Tests\TestCase;

uses(TestCase::class);

afterEach(function (): void {
    \Illuminate\Support\Facades\Config::set('app.upload_max_mb', env('APP_UPLOAD_MAX_MB', 12));
});

test('php upload limit returns default 12MB when env is not set', function (): void {
    config(['app.upload_max_mb' => 12]);

    expect(PhpUploadLimit::maxKilobytes())->toBe(12288);
});

test('php upload limit reads value from env', function (): void {
    config(['app.upload_max_mb' => 50]);

    expect(PhpUploadLimit::maxKilobytes())->toBe(51200);
});

test('php upload limit reads 10GB env value', function (): void {
    config(['app.upload_max_mb' => 10240]);

    expect(PhpUploadLimit::maxKilobytes())->toBe(10485760);
});

test('php upload limit falls back to default when env is zero or negative', function (): void {
    config(['app.upload_max_mb' => 0]);

    expect(PhpUploadLimit::maxKilobytes())->toBe(12288);
});

test('php upload limit falls back to default when env is negative', function (): void {
    config(['app.upload_max_mb' => -5]);

    expect(PhpUploadLimit::maxKilobytes())->toBe(12288);
});

<?php

use App\Util\Php\PhpUploadLimit;

afterEach(function (): void {
    putenv('APP_UPLOAD_MAX_MB=');
});

test('php upload limit returns default 12MB when env is not set', function (): void {
    putenv('APP_UPLOAD_MAX_MB=');

    expect(PhpUploadLimit::maxKilobytes())->toBe(12288);
});

test('php upload limit reads value from env', function (): void {
    putenv('APP_UPLOAD_MAX_MB=50');

    expect(PhpUploadLimit::maxKilobytes())->toBe(51200);
});

test('php upload limit reads 10GB env value', function (): void {
    putenv('APP_UPLOAD_MAX_MB=10240');

    expect(PhpUploadLimit::maxKilobytes())->toBe(10485760);
});

test('php upload limit falls back to default when env is zero or negative', function (): void {
    putenv('APP_UPLOAD_MAX_MB=0');

    expect(PhpUploadLimit::maxKilobytes())->toBe(12288);
});

test('php upload limit falls back to default when env is negative', function (): void {
    putenv('APP_UPLOAD_MAX_MB=-5');

    expect(PhpUploadLimit::maxKilobytes())->toBe(12288);
});

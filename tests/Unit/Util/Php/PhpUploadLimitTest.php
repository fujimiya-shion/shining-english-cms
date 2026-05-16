<?php

use App\Util\Php\PhpUploadLimit;

test('php upload limit returns fallback when php ini values are invalid', function (): void {
    $limit = new class extends PhpUploadLimit
    {
        protected static function readIniValue(string $option): string|false
        {
            return false;
        }
    };

    expect($limit::maxKilobytes())->toBe(12288);
});

test('php upload limit uses minimum between upload and post size', function (): void {
    $limit = new class extends PhpUploadLimit
    {
        protected static function readIniValue(string $option): string|false
        {
            return match ($option) {
                'upload_max_filesize' => '100M',
                'post_max_size' => '50M',
                default => false,
            };
        }
    };

    expect($limit::maxKilobytes())->toBe(51200);
});

test('php upload limit parses g m k and raw byte values', function (): void {
    expect(PhpUploadLimit::maxKilobytesFromIniValues('1G', '2G'))->toBe(1048576);
    expect(PhpUploadLimit::maxKilobytesFromIniValues('10M', '20M'))->toBe(10240);
    expect(PhpUploadLimit::maxKilobytesFromIniValues('2048K', '4M'))->toBe(2048);
    expect(PhpUploadLimit::maxKilobytesFromIniValues('2048', '4096'))->toBe(2);
});

test('php upload limit handles empty and whitespace values', function (): void {
    expect(PhpUploadLimit::maxKilobytesFromIniValues('', '   '))->toBe(12288);
});

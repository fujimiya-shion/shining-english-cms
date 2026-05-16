<?php

use App\Util\Video\VideoMetadataReader;
use Tests\TestCase;

uses(TestCase::class);

if (! class_exists('getID3')) {
    class getID3
    {
        /**
         * @return array<string, mixed>
         */
        public function analyze(string $absolutePath): array
        {
            return [
                'path' => $absolutePath,
                'playtime_seconds' => 120,
            ];
        }
    }
}

test('video metadata reader returns null when relative path is empty', function (): void {
    $reader = new VideoMetadataReader;

    expect($reader->detectDurationMinutes(null))->toBeNull();
    expect($reader->detectDurationMinutes(''))->toBeNull();
});

test('video metadata reader returns null when disk resolution fails', function (): void {
    $reader = new VideoMetadataReader;

    expect($reader->detectDurationMinutes('lessons/missing.mp4', 'invalid-disk'))->toBeNull();
});

test('video metadata reader resolves relative path and delegates to absolute path analyzer', function (): void {
    $reader = new class extends VideoMetadataReader
    {
        public ?string $capturedPath = null;

        public function detectDurationMinutesFromAbsolutePath(?string $absolutePath): ?int
        {
            $this->capturedPath = $absolutePath;

            return 7;
        }
    };

    $result = $reader->detectDurationMinutes('lessons/test.mp4');

    expect($result)->toBe(7);
    expect($reader->capturedPath)->toContain('/lessons/test.mp4');
});

test('video metadata reader returns null when absolute path is invalid', function (): void {
    $reader = new VideoMetadataReader;

    expect($reader->detectDurationMinutesFromAbsolutePath(null))->toBeNull();
    expect($reader->detectDurationMinutesFromAbsolutePath('/tmp/not-found.mp4'))->toBeNull();
});

test('video metadata reader returns null when analyzer has no numeric duration', function (): void {
    $reader = new class extends VideoMetadataReader
    {
        protected function analyzeFile(string $absolutePath): array
        {
            return [];
        }
    };
    $path = tempnam(sys_get_temp_dir(), 'reader-');
    file_put_contents($path, 'not-a-video');

    expect($reader->detectDurationMinutesFromAbsolutePath($path))->toBeNull();

    @unlink($path);
});

test('video metadata reader converts playtime seconds into minutes', function (): void {
    $reader = new class extends VideoMetadataReader
    {
        protected function analyzeFile(string $absolutePath): array
        {
            return ['playtime_seconds' => 61];
        }
    };

    $path = tempnam(sys_get_temp_dir(), 'reader-');
    file_put_contents($path, 'stub');

    expect($reader->detectDurationMinutesFromAbsolutePath($path))->toBe(2);

    @unlink($path);
});

test('video metadata reader returns null when playtime seconds are not positive', function (): void {
    $reader = new class extends VideoMetadataReader
    {
        protected function analyzeFile(string $absolutePath): array
        {
            return ['playtime_seconds' => 0];
        }
    };

    $path = tempnam(sys_get_temp_dir(), 'reader-');
    file_put_contents($path, 'stub');

    expect($reader->detectDurationMinutesFromAbsolutePath($path))->toBeNull();

    @unlink($path);
});

test('video metadata reader rounds up numeric string playtime to at least one minute', function (): void {
    $reader = new class extends VideoMetadataReader
    {
        protected function analyzeFile(string $absolutePath): array
        {
            return ['playtime_seconds' => '1'];
        }
    };

    $path = tempnam(sys_get_temp_dir(), 'reader-');
    file_put_contents($path, 'stub');

    expect($reader->detectDurationMinutesFromAbsolutePath($path))->toBe(1);

    @unlink($path);
});

test('video metadata reader catches analyzer exceptions and returns null', function (): void {
    $reader = new class extends VideoMetadataReader
    {
        protected function analyzeFile(string $absolutePath): array
        {
            throw new RuntimeException('analyze failed');
        }
    };

    $path = tempnam(sys_get_temp_dir(), 'reader-');
    file_put_contents($path, 'stub');

    expect($reader->detectDurationMinutesFromAbsolutePath($path))->toBeNull();

    @unlink($path);
});

test('video metadata reader analyze file returns analyzer metadata array', function (): void {
    $reader = new class extends VideoMetadataReader
    {
        /**
         * @return array<string, mixed>
         */
        public function callAnalyzeFile(string $absolutePath): array
        {
            return $this->analyzeFile($absolutePath);
        }
    };

    $path = tempnam(sys_get_temp_dir(), 'reader-');
    file_put_contents($path, 'stub');

    $metadata = $reader->callAnalyzeFile($path);

    expect($metadata)->toBeArray();
    expect($metadata)->not()->toBeEmpty();

    @unlink($path);
});

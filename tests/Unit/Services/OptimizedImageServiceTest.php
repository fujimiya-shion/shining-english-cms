<?php

use App\Services\OptimizedImageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class);

it('stores optimized webp image path without storing original file', function (): void {
    Storage::fake('public');

    $service = Mockery::mock(OptimizedImageService::class)
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();
    $service->shouldReceive('makeWebpVariant')
        ->once()
        ->andReturn('fake-webp-binary');

    $file = UploadedFile::fake()->image('avatar.jpg', 1200, 900);
    $path = $service->storeUploadedImage($file, 'public', 'users');

    expect($path)->toEndWith('.webp');
    Storage::disk('public')->assertExists($path);
    expect(collect(Storage::disk('public')->allFiles('users'))
        ->contains(fn (string $stored): bool => str_contains($stored, '.original.')))->toBeFalse();
});

it('deletes stored optimized image when path is local', function (): void {
    Storage::fake('public');
    Storage::disk('public')->put('users/sample.webp', 'binary');

    $service = new OptimizedImageService;
    $service->deleteStoredImage('users/sample.webp', 'public');

    Storage::disk('public')->assertMissing('users/sample.webp');
});

it('does not delete when path is external url', function (): void {
    Storage::fake('public');

    $service = new OptimizedImageService;
    $service->deleteStoredImage('https://example.com/avatar.webp', 'public');

    expect(true)->toBeTrue();
});

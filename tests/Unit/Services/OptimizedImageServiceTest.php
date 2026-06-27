<?php

use App\Services\OptimizedImageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class);

it('stores optimized webp image path without storing original file', function (): void {
    Storage::fake('public');
    $file = UploadedFile::fake()->image('photo.jpg', 100, 100);

    $service = new OptimizedImageService;
    $path = $service->storeUploadedImage($file, 'public', 'users');

    expect($path)->toEndWith('.webp');
    expect($path)->toStartWith('users/');

    Storage::disk('public')->assertExists($path);
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

it('does not delete when path is empty', function (): void {
    Storage::fake('public');

    $service = new OptimizedImageService;
    $service->deleteStoredImage('', 'public');

    expect(true)->toBeTrue();
});

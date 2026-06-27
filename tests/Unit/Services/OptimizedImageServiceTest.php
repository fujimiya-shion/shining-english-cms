<?php

use App\Services\OptimizedImageService;
use Illuminate\Contracts\Filesystem\Filesystem;
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

it('resolves a unique optimized storage path', function (): void {
    $storage = Mockery::mock(Filesystem::class);
    $storage->shouldReceive('exists')
        ->twice()
        ->andReturn(true, false);

    $service = new OptimizedImageService;
    $path = invokeProtectedMethod($service, 'resolveStoragePath', [$storage, 'avatars']);

    expect($path)->toStartWith('avatars/');
    expect($path)->toEndWith('.webp');
});

it('creates gd webp variants from supported image types', function (string $extension, callable $writer): void {
    $source = tempnam(sys_get_temp_dir(), 'optimized-image-').'.'.$extension;
    $writer($source);

    $service = new OptimizedImageService;
    $binary = invokeProtectedMethod($service, 'makeWebpVariantWithGd', [$source]);
    [$image, $width, $height] = invokeProtectedMethod($service, 'createGdImage', [$source]);

    expect($binary)->not->toBe('');
    expect($width)->toBe(4);
    expect($height)->toBe(4);

    imagedestroy($image);
    @unlink($source);
})->with([
    'jpeg' => ['jpg', function (string $path): void {
        $image = imagecreatetruecolor(4, 4);
        imagejpeg($image, $path);
        imagedestroy($image);
    }],
    'png' => ['png', function (string $path): void {
        $image = imagecreatetruecolor(4, 4);
        imagepng($image, $path);
        imagedestroy($image);
    }],
    'gif' => ['gif', function (string $path): void {
        $image = imagecreatetruecolor(4, 4);
        imagegif($image, $path);
        imagedestroy($image);
    }],
    'webp' => ['webp', function (string $path): void {
        $image = imagecreatetruecolor(4, 4);
        imagewebp($image, $path);
        imagedestroy($image);
    }],
]);

it('throws when image metadata cannot be read', function (): void {
    $source = tempnam(sys_get_temp_dir(), 'optimized-image-');
    file_put_contents($source, 'not an image');

    $service = new OptimizedImageService;

    expect(fn () => invokeProtectedMethod($service, 'createGdImage', [$source]))
        ->toThrow(\RuntimeException::class, 'Unable to read image metadata.');

    @unlink($source);
});

it('throws when image type is unsupported for gd optimization', function (): void {
    $source = tempnam(sys_get_temp_dir(), 'optimized-image-').'.bmp';
    $image = imagecreatetruecolor(4, 4);
    imagebmp($image, $source);
    imagedestroy($image);

    $service = new OptimizedImageService;

    expect(fn () => invokeProtectedMethod($service, 'createGdImage', [$source]))
        ->toThrow(\RuntimeException::class, 'Unsupported image type for optimization.');

    @unlink($source);
})->skip(! function_exists('imagebmp'), 'BMP writer is not available.');

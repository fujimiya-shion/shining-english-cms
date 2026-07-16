<?php

use App\Filament\Forms\Components\OptimizeFileUpload;
use App\Services\OptimizedImageService;
use Filament\Forms\Components\BaseFileUpload;
use League\Flysystem\UnableToCheckFileExistence;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

function optimizeUploadCallback(OptimizeFileUpload $component, string $property): ?Closure
{
    $reflection = new ReflectionProperty(BaseFileUpload::class, $property);

    return $reflection->getValue($component);
}

test('optimize file upload skips missing files', function (): void {
    $component = OptimizeFileUpload::make('thumbnail')->disk('public')->directory('courses');
    $file = Mockery::mock(TemporaryUploadedFile::class);
    $file->shouldReceive('exists')->once()->andReturnFalse();

    $callback = optimizeUploadCallback($component, 'saveUploadedFileUsing');

    expect($callback($component, $file))->toBeNull();
});

test('optimize file upload skips files when existence cannot be checked', function (): void {
    $component = OptimizeFileUpload::make('thumbnail')->disk('public')->directory('courses');
    $file = Mockery::mock(TemporaryUploadedFile::class);
    $file->shouldReceive('exists')->once()->andThrow(new UnableToCheckFileExistence('courses/thumb.jpg'));

    $callback = optimizeUploadCallback($component, 'saveUploadedFileUsing');

    expect($callback($component, $file))->toBeNull();
});

test('optimize file upload stores and deletes through optimized image service', function (): void {
    $component = OptimizeFileUpload::make('thumbnail')->disk('s3')->directory('courses');
    $file = Mockery::mock(TemporaryUploadedFile::class);
    $file->shouldReceive('exists')->once()->andReturnTrue();

    $service = Mockery::mock(OptimizedImageService::class);
    $service->shouldReceive('storeUploadedImage')
        ->once()
        ->with($file, 's3', 'courses')
        ->andReturn('courses/generated.webp');
    $service->shouldReceive('deleteStoredImage')
        ->once()
        ->with('courses/generated.webp', 's3');
    app()->instance(OptimizedImageService::class, $service);

    $saveCallback = optimizeUploadCallback($component, 'saveUploadedFileUsing');
    $deleteCallback = optimizeUploadCallback($component, 'deleteUploadedFileUsing');

    expect($saveCallback($component, $file))->toBe('courses/generated.webp');
    $deleteCallback($component, 'courses/generated.webp');
});

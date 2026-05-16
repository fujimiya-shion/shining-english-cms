<?php

namespace App\Filament\Forms\Components;

use App\Services\OptimizedImageService;
use App\Util\Php\PhpUploadLimit;
use Filament\Forms\Components\BaseFileUpload;
use Filament\Forms\Components\FileUpload;
use League\Flysystem\UnableToCheckFileExistence;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class OptimizeFileUpload extends FileUpload
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->image();
        $this->maxSize(PhpUploadLimit::maxKilobytes());

        $this->saveUploadedFileUsing(static function (BaseFileUpload $component, TemporaryUploadedFile $file): ?string {
            try {
                if (! $file->exists()) {
                    return null;
                }
            } catch (UnableToCheckFileExistence) {
                return null;
            }

            return app(OptimizedImageService::class)->storeUploadedImage(
                $file,
                disk: (string) ($component->getDiskName() ?? 'public'),
                directory: (string) ($component->getDirectory() ?? ''),
            );
        });

        $this->deleteUploadedFileUsing(static function (BaseFileUpload $component, string $file): void {
            app(OptimizedImageService::class)->deleteStoredImage(
                $file,
                disk: (string) ($component->getDiskName() ?? 'public'),
            );
        });
    }
}

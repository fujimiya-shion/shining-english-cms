<?php

namespace App\Services;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use RuntimeException;

class OptimizedImageService
{
    public function storeUploadedImage(
        UploadedFile|TemporaryUploadedFile $file,
        string $disk = 'public',
        string $directory = '',
    ): string {
        $directory = trim($directory, '/');
        $storage = Storage::disk($disk);
        $optimizedPath = $this->resolveStoragePath($storage, $directory);

        $optimizedBinary = $this->makeWebpVariant($file->getRealPath() ?: $file->getPathname());
        $storage->put($optimizedPath, $optimizedBinary);

        return $optimizedPath;
    }

    public function deleteStoredImage(string $path, string $disk = 'public'): void
    {
        if ($path === '' || Str::startsWith($path, ['http://', 'https://'])) {
            return;
        }

        $storage = Storage::disk($disk);

        if ($storage->exists($path)) {
            $storage->delete($path);
        }
    }

    protected function resolveStoragePath(Filesystem $storage, string $directory): string
    {
        do {
            $baseName = hash('sha256', Str::ulid() . '|' . microtime(true) . '|' . random_int(1, PHP_INT_MAX));
            $path = ltrim(($directory !== '' ? $directory . '/' : '') . $baseName . '.webp', '/');
        } while ($storage->exists($path));

        return $path;
    }

    protected function makeWebpVariant(string $sourcePath): string
    {
        if (extension_loaded('imagick') && class_exists(\Imagick::class)) {
            return $this->makeWebpVariantWithImagick($sourcePath);
        }

        return $this->makeWebpVariantWithGd($sourcePath);
    }

    protected function makeWebpVariantWithImagick(string $sourcePath): string
    {
        $quality = max(min((int) config('media.image_optimization.webp_quality', 82), 100), 1);
        $maxWidth = max((int) config('media.image_optimization.max_width', 512), 1);
        $maxHeight = max((int) config('media.image_optimization.max_height', 512), 1);

        $image = new \Imagick($sourcePath);

        if (method_exists($image, 'autoOrient')) {
            $image->autoOrient();
        } elseif (method_exists($image, 'autoOrientImage')) {
            $image->autoOrientImage();
        }

        $image = $image->coalesceImages();
        $image->setFirstIterator();

        $width = $image->getImageWidth();
        $height = $image->getImageHeight();

        if ($width > $maxWidth || $height > $maxHeight) {
            $image->thumbnailImage($maxWidth, $maxHeight, true, false);
            $image->setImagePage(0, 0, 0, 0);
        }

        $image->stripImage();
        $image->setImageFormat('webp');
        $image->setImageCompressionQuality($quality);

        $binary = $image->getImagesBlob();
        $image->clear();
        $image->destroy();

        if ($binary === '') {
            throw new RuntimeException('Unable to generate optimized webp image.');
        }

        return $binary;
    }

    protected function makeWebpVariantWithGd(string $sourcePath): string
    {
        if (! function_exists('imagewebp')) {
            throw new RuntimeException('GD webp support is not available.');
        }

        [$sourceImage, $width, $height] = $this->createGdImage($sourcePath);

        $maxWidth = max((int) config('media.image_optimization.max_width', 512), 1);
        $maxHeight = max((int) config('media.image_optimization.max_height', 512), 1);
        $quality = max(min((int) config('media.image_optimization.webp_quality', 82), 100), 1);

        $ratio = min($maxWidth / max($width, 1), $maxHeight / max($height, 1), 1);
        $targetWidth = max((int) round($width * $ratio), 1);
        $targetHeight = max((int) round($height * $ratio), 1);

        $targetImage = imagecreatetruecolor($targetWidth, $targetHeight);
        imagealphablending($targetImage, false);
        imagesavealpha($targetImage, true);
        $transparent = imagecolorallocatealpha($targetImage, 0, 0, 0, 127);
        imagefilledrectangle($targetImage, 0, 0, $targetWidth, $targetHeight, $transparent);
        imagecopyresampled($targetImage, $sourceImage, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        ob_start();
        imagewebp($targetImage, null, $quality);
        $binary = (string) ob_get_clean();

        imagedestroy($sourceImage);
        imagedestroy($targetImage);

        if ($binary === '') {
            throw new RuntimeException('Unable to generate optimized webp image.');
        }

        return $binary;
    }

    protected function createGdImage(string $sourcePath): array
    {
        $imageInfo = getimagesize($sourcePath);

        if ($imageInfo === false) {
            throw new RuntimeException('Unable to read image metadata.');
        }

        [$width, $height, $type] = $imageInfo;

        $image = match ($type) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($sourcePath),
            IMAGETYPE_PNG => imagecreatefrompng($sourcePath),
            IMAGETYPE_GIF => imagecreatefromgif($sourcePath),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? imagecreatefromwebp($sourcePath) : false,
            default => false,
        };

        if (! $image) {
            throw new RuntimeException('Unsupported image type for optimization.');
        }

        return [$image, $width, $height];
    }
}

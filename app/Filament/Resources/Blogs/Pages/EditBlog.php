<?php

namespace App\Filament\Resources\Blogs\Pages;

use App\Filament\Resources\Blogs\BlogResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EditBlog extends EditRecord
{
    protected static string $resource = BlogResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $thumbnail = isset($data['thumbnail']) ? trim((string) $data['thumbnail']) : '';
        $normalizedStorageUrl = rtrim((string) config('app.url'), '/').'/storage/';
        $isUrl = $thumbnail !== '' && filter_var($thumbnail, FILTER_VALIDATE_URL);
        $isLocalStorageUrl = $isUrl && Str::startsWith($thumbnail, $normalizedStorageUrl);

        $data['thumbnail_source'] = ($isUrl && ! $isLocalStorageUrl) ? 'url' : 'upload';
        $data['thumbnail_url'] = ($isUrl && ! $isLocalStorageUrl) ? $thumbnail : '';
        $data['thumbnail_file'] = $this->resolveThumbnailFilePath($thumbnail, $normalizedStorageUrl);

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $source = (string) ($data['thumbnail_source'] ?? 'upload');
        $thumbnailFile = isset($data['thumbnail_file']) ? trim((string) $data['thumbnail_file']) : '';
        $thumbnailUrl = isset($data['thumbnail_url']) ? trim((string) $data['thumbnail_url']) : '';
        $currentThumbnail = trim((string) ($this->record?->thumbnail ?? ''));
        $currentThumbnailFile = $this->resolveThumbnailFilePath(
            $currentThumbnail,
            rtrim((string) config('app.url'), '/').'/storage/',
        );

        if ($source === 'url') {
            if ($thumbnailUrl === '') {
                throw ValidationException::withMessages([
                    'thumbnail_url' => 'Vui lòng nhập Thumbnail URL.',
                ]);
            }
            if (! filter_var($thumbnailUrl, FILTER_VALIDATE_URL)) {
                throw ValidationException::withMessages([
                    'thumbnail_url' => 'Thumbnail URL không hợp lệ.',
                ]);
            }
        } elseif ($thumbnailFile === '') {
            if ($currentThumbnailFile !== '') {
                $thumbnailFile = $currentThumbnailFile;
            } else {
                throw ValidationException::withMessages([
                    'thumbnail_file' => 'Vui lòng upload thumbnail.',
                ]);
            }
        }

        if ($source === 'upload' && $thumbnailFile === '') {
            throw ValidationException::withMessages([
                'thumbnail_file' => 'Vui lòng upload thumbnail.',
            ]);
        }

        $data['thumbnail'] = $source === 'url' ? $thumbnailUrl : $thumbnailFile;

        unset($data['thumbnail_source'], $data['thumbnail_file'], $data['thumbnail_url']);

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    private function resolveThumbnailFilePath(string $thumbnail, string $normalizedStorageUrl): string
    {
        if ($thumbnail === '') {
            return '';
        }

        if (filter_var($thumbnail, FILTER_VALIDATE_URL) && Str::startsWith($thumbnail, $normalizedStorageUrl)) {
            return ltrim(Str::after($thumbnail, $normalizedStorageUrl), '/');
        }

        if (filter_var($thumbnail, FILTER_VALIDATE_URL)) {
            $path = (string) (parse_url($thumbnail, PHP_URL_PATH) ?? '');
            if ($path !== '' && Str::startsWith($path, '/storage/')) {
                return ltrim(Str::after($path, '/storage/'), '/');
            }
        }

        if (Str::startsWith($thumbnail, '/storage/')) {
            return ltrim(Str::after($thumbnail, '/storage/'), '/');
        }

        if (Str::startsWith($thumbnail, 'public/')) {
            return ltrim(Str::after($thumbnail, 'public/'), '/');
        }

        if (filter_var($thumbnail, FILTER_VALIDATE_URL)) {
            return '';
        }

        return ltrim($thumbnail, '/');
    }
}

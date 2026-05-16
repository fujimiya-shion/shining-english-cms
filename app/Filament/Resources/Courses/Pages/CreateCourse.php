<?php

namespace App\Filament\Resources\Courses\Pages;

use App\Filament\Resources\Courses\CourseResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateCourse extends CreateRecord
{
    protected static string $resource = CourseResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $source = (string) ($data['thumbnail_source'] ?? 'upload');
        $thumbnailFile = isset($data['thumbnail_file']) ? trim((string) $data['thumbnail_file']) : '';
        $thumbnailUrl = isset($data['thumbnail_url']) ? trim((string) $data['thumbnail_url']) : '';

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
            throw ValidationException::withMessages([
                'thumbnail_file' => 'Vui lòng upload thumbnail.',
            ]);
        }

        $data['thumbnail'] = $source === 'url' ? $thumbnailUrl : $thumbnailFile;

        unset($data['thumbnail_source'], $data['thumbnail_file'], $data['thumbnail_url']);

        return $data;
    }
}

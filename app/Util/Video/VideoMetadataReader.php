<?php

namespace App\Util\Video;

use Illuminate\Support\Facades\Storage;

class VideoMetadataReader
{
    public function detectDurationMinutes(?string $relativePath, string $disk = 'local'): ?int
    {
        if (! $relativePath) {
            return null;
        }

        try {
            $absolutePath = Storage::disk($disk)->path($relativePath);

            return $this->detectDurationMinutesFromAbsolutePath($absolutePath);
        } catch (\Throwable) {
            return null;
        }
    }

    public function detectDurationMinutesFromAbsolutePath(?string $absolutePath): ?int
    {
        if (! $absolutePath || ! is_file($absolutePath)) {
            return null;
        }

        try {
            $info = $this->analyzeFile($absolutePath);
            $seconds = $info['playtime_seconds'] ?? null;

            if (! is_numeric($seconds) || (float) $seconds <= 0) {
                return null;
            }

            return max(1, (int) ceil(((float) $seconds) / 60));
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function analyzeFile(string $absolutePath): array
    {
        $analyzer = new \getID3;

        return $analyzer->analyze($absolutePath);
    }
}

<?php

namespace App\Filament\Resources\Lessons\Pages;

use App\Filament\Resources\Lessons\LessonResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditLesson extends EditRecord
{
    protected static string $resource = LessonResource::class;

    protected function getHeaderActions(): array
    {
        $lesson = rescue(fn () => $this->record, null, false);

        if (! $lesson) {
            return [];
        }

        $courseSlug = $lesson->course?->slug;

        return [
            Action::make('viewOnWebsite')
                ->label('Xem trên website')
                ->icon('heroicon-o-eye')
                ->url($courseSlug
                    ? config('app.frontend_app_url')."/courses/{$courseSlug}?lessonId={$lesson->id}"
                    : null)
                ->openUrlInNewTab()
                ->visible((bool) $courseSlug),
        ];
    }
}

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
        $lesson = $this->record;
        $courseSlug = $lesson?->course?->slug;
        $lessonId = $lesson?->id;

        return [
            Action::make('viewOnWebsite')
                ->label('Xem trên website')
                ->icon('heroicon-o-eye')
                ->url($courseSlug && $lessonId
                    ? config('app.frontend_app_url')."/courses/{$courseSlug}?lessonId={$lessonId}"
                    : null)
                ->openUrlInNewTab()
                ->visible((bool) $courseSlug),
        ];
    }
}

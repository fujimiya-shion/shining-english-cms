<?php

namespace App\Filament\Resources\Quizzes\Pages;

use App\Filament\Resources\Quizzes\QuizResource;
use App\Filament\Resources\Quizzes\Widgets\QuizAttemptsOverview;
use App\Filament\Resources\Quizzes\Widgets\QuizUserStatsTable;
use Filament\Resources\Pages\EditRecord;

class EditQuiz extends EditRecord
{
    protected static string $resource = QuizResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            QuizAttemptsOverview::class,
            QuizUserStatsTable::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
    }

    public function getWidgetData(): array
    {
        return [
            'record' => $this->record,
        ];
    }
}

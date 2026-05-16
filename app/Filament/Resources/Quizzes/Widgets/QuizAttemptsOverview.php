<?php

namespace App\Filament\Resources\Quizzes\Widgets;

use App\Models\Quiz;
use App\Models\UserQuizAttempt;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class QuizAttemptsOverview extends StatsOverviewWidget
{
    public ?Quiz $record = null;

    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = 'Quiz Attempt Analytics';

    protected function getStats(): array
    {
        $quizId = $this->record?->id;

        if (! $quizId) {
            return [];
        }

        $attemptsQuery = UserQuizAttempt::query()->where('quiz_id', $quizId);
        $totalSubmissions = (clone $attemptsQuery)->count();
        $submittedUsers = (clone $attemptsQuery)->distinct('user_id')->count('user_id');
        $passedCount = (clone $attemptsQuery)->where('passed', true)->count();
        $failedCount = max(0, $totalSubmissions - $passedCount);
        $highestScore = (clone $attemptsQuery)->max('score_percent');
        $lowestScore = (clone $attemptsQuery)->min('score_percent');

        $passRate = $totalSubmissions > 0
            ? ($passedCount / $totalSubmissions) * 100
            : 0;
        $failedRate = $totalSubmissions > 0
            ? ($failedCount / $totalSubmissions) * 100
            : 0;

        return [
            Stat::make('Total Submissions', number_format($totalSubmissions))
                ->description(number_format($submittedUsers).' unique users submitted')
                ->descriptionIcon(Heroicon::Users)
                ->icon(Heroicon::ClipboardDocumentCheck)
                ->color('primary'),
            Stat::make('Pass Rate', number_format($passRate, 1).'%')
                ->description(number_format($passedCount).' passed submissions')
                ->descriptionIcon(Heroicon::CheckCircle)
                ->icon(Heroicon::ArrowTrendingUp)
                ->color('success'),
            Stat::make('Fail Rate', number_format($failedRate, 1).'%')
                ->description(number_format($failedCount).' failed submissions')
                ->descriptionIcon(Heroicon::XCircle)
                ->icon(Heroicon::ArrowTrendingDown)
                ->color('danger'),
            Stat::make('Highest Score', $highestScore !== null ? number_format((float) $highestScore, 1).'%' : '-')
                ->description('Best attempt score')
                ->descriptionIcon(Heroicon::Trophy)
                ->icon(Heroicon::ArrowUpCircle)
                ->color('success'),
            Stat::make('Lowest Score', $lowestScore !== null ? number_format((float) $lowestScore, 1).'%' : '-')
                ->description('Lowest attempt score')
                ->descriptionIcon(Heroicon::ArrowDownCircle)
                ->icon(Heroicon::ArrowDownCircle)
                ->color('warning'),
        ];
    }
}

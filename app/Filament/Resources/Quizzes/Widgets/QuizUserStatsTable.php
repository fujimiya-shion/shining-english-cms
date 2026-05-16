<?php

namespace App\Filament\Resources\Quizzes\Widgets;

use App\Models\Quiz;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class QuizUserStatsTable extends TableWidget
{
    public ?Quiz $record = null;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $quizId = $this->record?->id;

        return $table
            ->query($this->buildQuery($quizId))
            ->defaultSort('last_submitted_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label('User')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                TextColumn::make('submissions_count')
                    ->label('Submissions')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('pass_rate')
                    ->label('Pass %')
                    ->numeric(1)
                    ->suffix('%')
                    ->badge()
                    ->color(fn (float $state): string => $state >= 50 ? 'success' : 'danger')
                    ->sortable(),
                TextColumn::make('passed_count')
                    ->label('Passed')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('failed_count')
                    ->label('Failed')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('best_score')
                    ->label('Highest')
                    ->numeric(1)
                    ->suffix('%')
                    ->sortable(),
                TextColumn::make('lowest_score')
                    ->label('Lowest')
                    ->numeric(1)
                    ->suffix('%')
                    ->sortable(),
                TextColumn::make('last_submitted_at')
                    ->label('Last Submit')
                    ->dateTime()
                    ->sortable(),
            ])
            ->paginated([10, 25, 50]);
    }

    protected function getTableHeading(): ?string
    {
        return 'Per-user Attempt Analytics';
    }

    /**
     * @return Builder<User>
     */
    private function buildQuery(?int $quizId): Builder
    {
        $query = User::query()
            ->select('users.id', 'users.name', 'users.email')
            ->selectRaw('COUNT(user_quiz_attempts.id) as submissions_count')
            ->selectRaw('SUM(CASE WHEN user_quiz_attempts.passed = 1 THEN 1 ELSE 0 END) as passed_count')
            ->selectRaw('SUM(CASE WHEN user_quiz_attempts.passed = 0 THEN 1 ELSE 0 END) as failed_count')
            ->selectRaw('MAX(user_quiz_attempts.score_percent) as best_score')
            ->selectRaw('MIN(user_quiz_attempts.score_percent) as lowest_score')
            ->selectRaw('MAX(user_quiz_attempts.submitted_at) as last_submitted_at')
            ->selectRaw(
                'CASE WHEN COUNT(user_quiz_attempts.id) > 0 
                    THEN (SUM(CASE WHEN user_quiz_attempts.passed = 1 THEN 1 ELSE 0 END) / COUNT(user_quiz_attempts.id)) * 100
                    ELSE 0 END as pass_rate'
            )
            ->join('user_quiz_attempts', 'user_quiz_attempts.user_id', '=', 'users.id');

        if ($quizId) {
            $query->where('user_quiz_attempts.quiz_id', $quizId);
        } else {
            $query->whereRaw('1 = 0');
        }

        return $query->groupBy('users.id', 'users.name', 'users.email');
    }
}

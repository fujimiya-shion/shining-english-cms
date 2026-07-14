<?php

namespace App\Filament\Resources\Quizzes\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class QuizForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Thông tin quiz')
                    ->compact()
                    ->columns(12)
                    ->schema([
                        Select::make('lesson_id')
                            ->label('Bài học')
                            ->relationship('lesson', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->columnSpan(12),
                        TextInput::make('pass_percent')
                            ->label('Điểm đạt (%)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(1)
                            ->default(80)
                            ->required()
                            ->helperText('Học viên cần đạt tối thiểu % này để vượt qua quiz.')
                            ->columnSpan(4),
                    ]),
            ]);
    }
}

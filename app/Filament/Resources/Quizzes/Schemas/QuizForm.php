<?php

namespace App\Filament\Resources\Quizzes\Schemas;

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
                        TextInput::make('name')
                            ->label('Tên bộ câu hỏi')
                            ->required()
                            ->maxLength(255)
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
                            ->columnSpan(12),
                    ]),
            ]);
    }
}

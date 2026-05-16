<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Filament\Forms\Components\OptimizeFileUpload;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Grid::make(12)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(6),
                        TextInput::make('nickname')
                            ->maxLength(255)
                            ->columnSpan(6),
                        TextInput::make('email')
                            ->label('Email address')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->columnSpan(6),
                        TextInput::make('phone')
                            ->tel()
                            ->required()
                            ->maxLength(20)
                            ->columnSpan(4),
                        DatePicker::make('birthday')
                            ->columnSpan(4),
                        Select::make('city_id')
                            ->relationship('city', 'name')
                            ->searchable()
                            ->preload()
                            ->columnSpan(4),
                        OptimizeFileUpload::make('avatar')
                            ->image()
                            ->disk('public')
                            ->directory('users')
                            ->visibility('public')
                            ->imageEditor()
                            ->maxSize(2048)
                            ->columnSpan(6),
                        DateTimePicker::make('email_verified_at')
                            ->columnSpan(6),
                        TextInput::make('password')
                            ->password()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->columnSpan(6),
                    ]),
            ]);
    }
}

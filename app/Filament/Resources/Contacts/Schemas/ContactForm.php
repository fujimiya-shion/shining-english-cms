<?php

namespace App\Filament\Resources\Contacts\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class ContactForm
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
                            ->disabled()
                            ->columnSpan(6),
                        TextInput::make('email')
                            ->disabled()
                            ->columnSpan(6),
                    ]),
                Textarea::make('message')
                    ->disabled()
                    ->rows(6)
                    ->columnSpanFull(),
                TextInput::make('ip_address')
                    ->disabled()
                    ->columnSpanFull(),
                Textarea::make('user_agent')
                    ->disabled()
                    ->rows(3)
                    ->columnSpanFull(),
                TextInput::make('reply_subject')
                    ->disabled()
                    ->columnSpanFull(),
                Textarea::make('reply_message')
                    ->disabled()
                    ->rows(4)
                    ->columnSpanFull(),
            ]);
    }
}


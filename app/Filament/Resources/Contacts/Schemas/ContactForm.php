<?php

namespace App\Filament\Resources\Contacts\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ContactForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Thông tin người gửi')
                    ->compact()
                    ->columns(12)
                    ->schema([
                        TextInput::make('name')
                            ->label('Tên')
                            ->disabled()
                            ->columnSpan(6),
                        TextInput::make('email')
                            ->label('Email')
                            ->disabled()
                            ->columnSpan(6),
                        Textarea::make('message')
                            ->label('Nội dung')
                            ->disabled()
                            ->rows(6)
                            ->columnSpan(12),
                        TextInput::make('ip_address')
                            ->label('IP')
                            ->disabled()
                            ->columnSpan(4),
                        Textarea::make('user_agent')
                            ->label('Trình duyệt')
                            ->disabled()
                            ->rows(3)
                            ->columnSpan(8),
                    ]),
                Section::make('Phản hồi')
                    ->compact()
                    ->columns(12)
                    ->schema([
                        TextInput::make('reply_subject')
                            ->label('Chủ đề phản hồi')
                            ->disabled()
                            ->columnSpan(6),
                        Textarea::make('reply_message')
                            ->label('Nội dung phản hồi')
                            ->disabled()
                            ->rows(4)
                            ->columnSpan(12),
                    ]),
            ]);
    }
}

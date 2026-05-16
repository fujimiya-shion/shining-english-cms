<?php

namespace App\Filament\Resources\Blogs\Schemas;

use App\Filament\Forms\Components\OptimizeFileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class BlogForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Grid::make(12)
                    ->columnSpanFull()
                    ->schema([
                        Toggle::make('status')
                            ->required()
                            ->inline(false)
                            ->default(true)
                            ->columnSpanFull(),
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(8),
                        TextInput::make('slug')
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->helperText('Auto-generate from title if left unchanged.')
                            ->columnSpan(4),
                        Select::make('tag_id')
                            ->relationship('tag', 'name')
                            ->searchable()
                            ->preload()
                            ->columnSpan(4),
                        TextInput::make('required_star')
                            ->label('Required stars')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->step(1)
                            ->columnSpan(4),
                        Textarea::make('short_description')
                            ->label('Short description')
                            ->helperText('Plain text, ưu tiên hiển thị ở card blog.')
                            ->rows(3)
                            ->maxLength(500)
                            ->columnSpan(12),
                        Select::make('thumbnail_source')
                            ->label('Thumbnail source')
                            ->options([
                                'upload' => 'Upload ảnh',
                                'url' => 'Dùng URL ngoài',
                            ])
                            ->default('upload')
                            ->native(false)
                            ->live()
                            ->columnSpan(4),
                        Hidden::make('thumbnail')
                            ->dehydrated(true),
                        OptimizeFileUpload::make('thumbnail_file')
                            ->label('Thumbnail')
                            ->image()
                            ->disk('public')
                            ->directory('blogs')
                            ->visibility('public')
                            ->imageEditor()
                            ->maxSize(2048)
                            ->visible(fn (Get $get): bool => $get('thumbnail_source') !== 'url')
                            ->dehydrated(true)
                            ->columnSpan(12),
                        TextInput::make('thumbnail_url')
                            ->label('Thumbnail URL')
                            ->placeholder('https://example.com/blog-thumbnail.jpg')
                            ->url()
                            ->maxLength(2048)
                            ->visible(fn (Get $get): bool => $get('thumbnail_source') === 'url')
                            ->dehydrated(true)
                            ->columnSpan(12),
                        TextInput::make('description')
                            ->required()
                            ->maxLength(500)
                            ->columnSpan(12),
                        RichEditor::make('content')
                            ->required()
                            ->columnSpan(12),
                    ]),
            ]);
    }
}

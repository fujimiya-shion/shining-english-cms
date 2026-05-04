<?php

namespace App\Filament\Resources\Courses\Schemas;

use App\Filament\Forms\Components\OptimizeFileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class CourseForm
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
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(8),
                        TextInput::make('slug')
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->helperText('Auto-generate from name if left unchanged.')
                            ->columnSpan(4),
                        Select::make('category_id')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpan(4),
                        Select::make('level_id')
                            ->relationship('level', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpan(4),
                        TextInput::make('price')
                            ->required()
                            ->numeric()
                            ->prefix('VND')
                            ->minValue(0)
                            ->columnSpan(3),
                        TextInput::make('rating')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(5)
                            ->step(0.1)
                            ->columnSpan(3),
                        TextInput::make('learned')
                            ->numeric()
                            ->minValue(0)
                            ->step(1)
                            ->columnSpan(3),
                        OptimizeFileUpload::make('thumbnail')
                            ->image()
                            ->disk('public')
                            ->directory('courses')
                            ->visibility('public')
                            ->imageEditor()
                            ->maxSize(2048)
                            ->columnSpan(12),
                        RichEditor::make('description')
                            ->columnSpan(12),
                    ]),
            ]);
    }
}

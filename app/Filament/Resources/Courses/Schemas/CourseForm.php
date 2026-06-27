<?php

namespace App\Filament\Resources\Courses\Schemas;

use App\Filament\Forms\Components\OptimizeFileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class CourseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Thông tin cơ bản')
                    ->compact()
                    ->columns(12)
                    ->schema([
                        Toggle::make('status')
                            ->required()
                            ->inline(false)
                            ->default(true)
                            ->columnSpanFull(),
                        TextInput::make('name')
                            ->label('Tên khóa học')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(8),
                        TextInput::make('slug')
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->disabled(fn (Get $get): bool => ! (bool) $get('customize_slug'))
                            ->helperText('Mặc định tự động tạo từ tên khóa học. Bật toggle để tự nhập.')
                            ->columnSpan(4),
                        Toggle::make('customize_slug')
                            ->label('Tự chỉnh sửa slug')
                            ->default(false)
                            ->live()
                            ->dehydrated(false)
                            ->columnSpan(4)
                            ->columnStart(9),
                        Select::make('category_id')
                            ->label('Danh mục')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpan(4),
                        Select::make('level_id')
                            ->label('Trình độ')
                            ->relationship('level', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpan(4),
                        TextInput::make('price')
                            ->label('Giá (VND)')
                            ->required()
                            ->numeric()
                            ->prefix('VND')
                            ->minValue(0)
                            ->columnSpan(4),
                        RichEditor::make('description')
                            ->label('Mô tả')
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
                            ->directory('courses')
                            ->visibility('public')
                            ->imageEditor()
                            ->maxSize(2048)
                            ->visible(fn (Get $get): bool => $get('thumbnail_source') !== 'url')
                            ->dehydrated(true)
                            ->columnSpan(12),
                        TextInput::make('thumbnail_url')
                            ->label('Thumbnail URL')
                            ->placeholder('https://example.com/thumbnail.jpg')
                            ->url()
                            ->maxLength(2048)
                            ->visible(fn (Get $get): bool => $get('thumbnail_source') === 'url')
                            ->dehydrated(true)
                            ->columnSpan(12),
                    ]),
                Section::make('Thanh toán bằng sao')
                    ->compact()
                    ->columns(12)
                    ->schema([
                        Toggle::make('allow_star_payment')
                            ->label('Cho phép thanh toán bằng sao')
                            ->helperText('Bật để cho phép học viên dùng sao mở khóa học này.')
                            ->inline(false)
                            ->default(false)
                            ->live()
                            ->columnSpanFull(),
                        TextInput::make('star_price')
                            ->label('Số sao cần để mở')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->step(1)
                            ->visible(fn (Get $get): bool => (bool) $get('allow_star_payment'))
                            ->columnSpan(4),
                    ]),
            ]);
    }
}

<?php

namespace App\Filament\Resources\Blogs\Schemas;

use App\Filament\Forms\Components\OptimizeFileUpload;
use App\Util\Php\PhpUploadLimit;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class BlogForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Thông tin bài viết')
                    ->compact()
                    ->columns(12)
                    ->schema([
                        Toggle::make('status')
                            ->label('Trạng thái')
                            ->required()
                            ->inline(false)
                            ->default(true)
                            ->columnSpanFull(),
                        TextInput::make('title')
                            ->label('Tiêu đề')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(8),
                        TextInput::make('slug')
                            ->label('Slug')
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->disabled(fn (Get $get): bool => ! (bool) $get('customize_slug'))
                            ->helperText('Mặc định tự động tạo từ tiêu đề. Bật toggle để tự nhập.')
                            ->columnSpan(4),
                        Toggle::make('customize_slug')
                            ->label('Tự chỉnh sửa slug')
                            ->default(false)
                            ->live()
                            ->dehydrated(false)
                            ->columnSpan(4)
                            ->columnStart(9),
                        Select::make('tag_id')
                            ->label('Chủ đề')
                            ->relationship('tag', 'name')
                            ->searchable()
                            ->preload()
                            ->columnSpan(4),
                        Textarea::make('short_description')
                            ->label('Mô tả ngắn')
                            ->helperText('Plain text, ưu tiên hiển thị ở card blog.')
                            ->rows(3)
                            ->maxLength(500)
                            ->columnSpan(12),
                        Textarea::make('description')
                            ->label('Mô tả dài')
                            ->required()
                            ->maxLength(500)
                            ->columnSpan(12),
                        RichEditor::make('content')
                            ->label('Nội dung')
                            ->required()
                            ->columnSpan(12),
                    ]),
                Section::make('Ảnh bìa')
                    ->compact()
                    ->columns(12)
                    ->schema([
                        Select::make('thumbnail_source')
                            ->label('Nguồn ảnh')
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
                            ->label('Ảnh bìa')
                            ->image()
                            ->disk('public')
                            ->directory('blogs')
                            ->visibility('public')
                            ->imageEditor()
                            ->maxSize(PhpUploadLimit::maxKilobytes())
                            ->visible(fn (Get $get): bool => $get('thumbnail_source') !== 'url')
                            ->dehydrated(true)
                            ->columnSpan(12),
                        TextInput::make('thumbnail_url')
                            ->label('URL ảnh bìa')
                            ->placeholder('https://example.com/blog-thumbnail.jpg')
                            ->url()
                            ->maxLength(2048)
                            ->visible(fn (Get $get): bool => $get('thumbnail_source') === 'url')
                            ->dehydrated(true)
                            ->columnSpan(12),
                    ]),
            ]);
    }
}

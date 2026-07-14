<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Filament\Forms\Components\OptimizeFileUpload;
use App\Util\Php\PhpUploadLimit;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Thông tin cá nhân')
                    ->compact()
                    ->columns(12)
                    ->schema([
                        TextInput::make('name')
                            ->label('Họ tên')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(6),
                        TextInput::make('nickname')
                            ->label('Biệt danh')
                            ->maxLength(255)
                            ->columnSpan(6),
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->columnSpan(6),
                        TextInput::make('phone')
                            ->label('Số điện thoại')
                            ->tel()
                            ->required()
                            ->maxLength(20)
                            ->columnSpan(4),
                        DatePicker::make('birthday')
                            ->label('Ngày sinh')
                            ->columnSpan(4),
                        Select::make('city_id')
                            ->label('Thành phố')
                            ->relationship('city', 'name')
                            ->searchable()
                            ->preload()
                            ->columnSpan(4),
                        OptimizeFileUpload::make('avatar')
                            ->label('Ảnh đại diện')
                            ->image()
                            ->disk('public')
                            ->directory('users')
                            ->visibility('public')
                            ->imageEditor()
                            ->maxSize(PhpUploadLimit::maxKilobytes())
                            ->columnSpan(6),
                        DateTimePicker::make('email_verified_at')
                            ->label('Email xác thực lúc')
                            ->columnSpan(6),
                    ]),
                Section::make('Bảo mật')
                    ->compact()
                    ->columns(12)
                    ->schema([
                        TextInput::make('password')
                            ->label('Mật khẩu mới')
                            ->password()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->helperText('Chỉ cần nhập nếu muốn thay đổi mật khẩu.')
                            ->columnSpan(6),
                    ]),
            ]);
    }
}

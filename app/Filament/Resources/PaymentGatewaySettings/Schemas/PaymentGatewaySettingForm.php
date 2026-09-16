<?php

declare(strict_types=1);

namespace App\Filament\Resources\PaymentGatewaySettings\Schemas;

use App\Enums\GatewayFieldRegistry;
use App\Enums\GatewayType;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get as SchemaGet;
use Filament\Schemas\Schema;

class PaymentGatewaySettingForm
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
                        TextInput::make('name')
                            ->label('Tên cổng thanh toán')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpan(6),
                        TextInput::make('slug')
                            ->label('Mã')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpan(6),
                        Toggle::make('is_active')
                            ->label('Kích hoạt')
                            ->inline(false)
                            ->default(false)
                            ->columnSpanFull(),
                    ]),
                static::makeSettingsSection(),
            ]);
    }

    protected static function makeSettingsSection(): Section
    {
        return Section::make('Cấu hình tham số')
            ->compact()
            ->description('Thêm, chỉnh sửa hoặc xóa các tham số cấu hình cho cổng thanh toán. Các trường bắt buộc không thể xóa.')
            ->schema([
                Repeater::make('settings_fields')
                    ->label('')
                    ->table([
                        TableColumn::make('Trường')
                            ->width('250px'),
                        TableColumn::make('Giá trị'),
                    ])
                    ->schema([
                        Select::make('key')
                            ->label('Tên trường')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set, ?string $state): void {
                                $gatewayType = GatewayType::tryFrom($get('../../slug'));
                                if ($gatewayType === null || $state === null) {
                                    return;
                                }
                                $field = GatewayFieldRegistry::getField($gatewayType, $state);
                                if ($field === null) {
                                    return;
                                }
                                $set('type', $field['type']);
                                $set('value', '');
                            })
                            ->options(function (SchemaGet $get): array {
                                $gatewayType = GatewayType::tryFrom($get('../../slug'));
                                if ($gatewayType === null) {
                                    return [];
                                }

                                return GatewayFieldRegistry::getSelectableOptions($gatewayType);
                            })
                            ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                            ->required(),
                        TextInput::make('value')
                            ->label('Giá trị')
                            ->maxLength(2048)
                            ->placeholder(fn (SchemaGet $get): string => match ($get('type') ?? 'text') {
                                'url' => 'https://example.com/webhook',
                                default => 'Nhập giá trị...',
                            })
                            ->password(fn (SchemaGet $get): bool => ($get('type') ?? 'text') === 'password')
                            ->revealable(fn (SchemaGet $get): bool => ($get('type') ?? 'text') === 'password'),
                        TextInput::make('type')
                            ->hidden(),
                    ])
                    ->defaultItems(0)
                    ->addActionLabel('Thêm tham số')
                    ->reorderableWithButtons()
                    ->collapsible()
                    ->columns(1),
            ])
            ->visible(fn (SchemaGet $get): bool => in_array($get('slug'), array_column(GatewayType::cases(), 'value')));
    }
}

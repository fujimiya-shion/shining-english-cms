<?php

namespace App\Filament\Resources\Orders;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Filament\Resources\BaseResource;
use App\Filament\Resources\Orders\Pages\EditOrder;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Filament\Resources\Orders\RelationManagers\ItemsRelationManager;
use App\Filament\Resources\Orders\Tables\OrdersTable;
use App\Models\Order;
use App\Services\IService;
use App\Services\Order\IOrderService;
use BackedEnum;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class OrderResource extends BaseResource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ShoppingBag;

    protected static ?string $recordTitleAttribute = 'id';

    protected static function service(): IService
    {
        return app(IOrderService::class);
    }

    protected static function getListEagerLoads(): array
    {
        return ['user'];
    }

    protected static function getRecordEagerLoads(): array
    {
        return ['user', 'items.course'];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Grid::make(12)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('id')
                            ->label('Order')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpan(3),
                        Placeholder::make('user_name')
                            ->label('User')
                            ->content(fn (?Order $record): string => $record?->user?->name ?? '-')
                            ->columnSpan(5),
                        Placeholder::make('user_email')
                            ->label('Email')
                            ->content(fn (?Order $record): string => $record?->user?->email ?? '-')
                            ->columnSpan(4),
                        TextInput::make('total_amount')
                            ->label('Total')
                            ->numeric()
                            ->minValue(0)
                            ->required()
                            ->columnSpan(4),
                        Select::make('status')
                            ->options(collect(OrderStatus::cases())->mapWithKeys(
                                static fn (OrderStatus $status): array => [$status->value => ucfirst($status->value)]
                            )->all())
                            ->required()
                            ->native(false)
                            ->columnSpan(4),
                        Select::make('payment_method')
                            ->label('Payment')
                            ->options(collect(PaymentMethod::cases())->mapWithKeys(
                                static fn (PaymentMethod $method): array => [$method->value => strtoupper($method->value)]
                            )->all())
                            ->required()
                            ->native(false)
                            ->columnSpan(4),
                        DateTimePicker::make('placed_at')
                            ->label('Placed At')
                            ->seconds(false)
                            ->required()
                            ->columnSpan(6),
                        DateTimePicker::make('created_at')
                            ->disabled()
                            ->dehydrated(false)
                            ->seconds(false)
                            ->columnSpan(3),
                        DateTimePicker::make('updated_at')
                            ->disabled()
                            ->dehydrated(false)
                            ->seconds(false)
                            ->columnSpan(3),
                    ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextEntry::make('id')
                    ->label('Order'),
                TextEntry::make('user.name')
                    ->label('User'),
                TextEntry::make('user.email')
                    ->label('Email'),
                TextEntry::make('total_amount')
                    ->label('Total')
                    ->money('VND'),
                TextEntry::make('status')
                    ->badge(),
                TextEntry::make('payment_method')
                    ->label('Payment')
                    ->badge(),
                TextEntry::make('placed_at')
                    ->label('Placed At')
                    ->dateTime(),
                TextEntry::make('created_at')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->dateTime(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return OrdersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'view' => ViewOrder::route('/{record}'),
            'edit' => EditOrder::route('/{record}/edit'),
        ];
    }
}

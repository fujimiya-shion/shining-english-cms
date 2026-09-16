<?php

declare(strict_types=1);

namespace App\Filament\Resources\PaymentGatewaySettings;

use App\Filament\Resources\BaseResource;
use App\Filament\Resources\PaymentGatewaySettings\Pages\EditPaymentGatewaySetting;
use App\Filament\Resources\PaymentGatewaySettings\Pages\ListPaymentGatewaySettings;
use App\Filament\Resources\PaymentGatewaySettings\Schemas\PaymentGatewaySettingForm;
use App\Filament\Resources\PaymentGatewaySettings\Tables\PaymentGatewaySettingsTable;
use App\Models\PaymentGatewaySetting;
use App\Services\IService;
use App\Services\PaymentGatewaySetting\IPaymentGatewaySettingService;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PaymentGatewaySettingResource extends BaseResource
{
    protected static ?string $model = PaymentGatewaySetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::CreditCard;

    protected static ?string $navigationLabel = 'Cổng thanh toán';

    protected static ?string $modelLabel = 'Cổng thanh toán';

    protected static ?string $pluralModelLabel = 'Cổng thanh toán';

    protected static ?string $slug = 'payment-gateway-settings';

    protected static ?int $navigationSort = 1;

    protected static function service(): IService
    {
        return app(IPaymentGatewaySettingService::class);
    }

    public static function form(Schema $schema): Schema
    {
        return PaymentGatewaySettingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PaymentGatewaySettingsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPaymentGatewaySettings::route('/'),
            'edit' => EditPaymentGatewaySetting::route('/{record}/edit'),
        ];
    }
}

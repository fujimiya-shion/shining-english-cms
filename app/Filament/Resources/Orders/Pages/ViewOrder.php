<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        $orderCode = $this->record?->order_code;

        return [
            EditAction::make(),
            Action::make('viewOnWebsite')
                ->label('Xem trên website')
                ->icon('heroicon-o-eye')
                ->url($orderCode ? config('app.frontend_app_url')."/orders/{$orderCode}" : null)
                ->openUrlInNewTab()
                ->visible((bool) $orderCode),
        ];
    }
}

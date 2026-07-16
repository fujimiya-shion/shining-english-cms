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
        $actions = rescue(fn () => [EditAction::make()], [], false);

        $orderCode = rescue(fn () => $this->record?->order_code, null, false);

        if ($orderCode) {
            $actions[] = Action::make('viewOnWebsite')
                ->label('Xem trên website')
                ->icon('heroicon-o-eye')
                ->url(config('app.frontend_app_url')."/orders/{$orderCode}")
                ->openUrlInNewTab();
        }

        return $actions;
    }
}

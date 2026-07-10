<?php

namespace App\Filament\Resources\Admins;

use App\Filament\Resources\Admins\Pages\CreateAdmin;
use App\Filament\Resources\Admins\Pages\EditAdmin;
use App\Filament\Resources\Admins\Pages\ListAdmins;
use App\Filament\Resources\Admins\Schemas\AdminForm;
use App\Filament\Resources\Admins\Tables\AdminsTable;
use App\Filament\Resources\BaseResource;
use App\Models\Admin;
use App\Services\Admin\IAdminService;
use App\Services\IService;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class AdminResource extends BaseResource
{
    protected static ?string $model = Admin::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ShieldCheck;

    protected static ?string $recordTitleAttribute = 'Admin';

    protected static function service(): IService
    {
        return app(IAdminService::class);
    }

    public static function form(Schema $schema): Schema
    {
        return AdminForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AdminsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAdmins::route('/'),
            'create' => CreateAdmin::route('/create'),
            'edit' => EditAdmin::route('/{record}/edit'),
        ];
    }
}

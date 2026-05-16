<?php

namespace App\Filament\Resources;

use App\Services\IService;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;

abstract class BaseResource extends Resource
{
    abstract protected static function service(): IService;

    protected static function getListEagerLoads(): array
    {
        return [];
    }

    protected static function getRecordEagerLoads(): array
    {
        return static::getListEagerLoads();
    }

    public static function getEloquentQuery(): Builder
    {
        return static::service()->query(static::getListEagerLoads());
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return static::service()->query(static::getRecordEagerLoads());
    }
}

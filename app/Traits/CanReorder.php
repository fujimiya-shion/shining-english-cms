<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;

/** @mixin Model */
trait CanReorder
{
    public static function bootCanReorder(): void
    {
        static::creating(function (Model $model): void {
            if ($model->order === null || $model->order === 0) {
                $model->order = (int) ($model::query()->max('order') ?? 0) + 1;
            }
        });
    }
}

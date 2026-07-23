<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;

/** @mixin Model */
trait CanReorder
{
    public static function bootCanReorder(): void
    {
        static::created(function (Model $model): void {
            if ($model->order === null || $model->order === 0) {
                $model->updateQuietly(['order' => $model->id]);
            }
        });
    }
}

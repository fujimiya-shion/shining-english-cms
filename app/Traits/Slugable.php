<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait Slugable
{
    protected static function bootSlugable(): void
    {
        static::creating(function ($model) {
            if (static::shouldGenerateSlugOnCreate($model)) {
                $value = static::getSlugSourceValue($model);
                if (! empty($value)) {
                    $model->slug = static::generateUniqueSlug($model, $value);
                }
            }
        });

        static::updating(function ($model) {
            if (static::shouldGenerateSlugOnUpdate($model)) {
                $value = static::getSlugSourceValue($model);
                if (! empty($value)) {
                    $model->slug = static::generateUniqueSlug($model, $value);
                }
            }
        });
    }

    protected static function getSlugSourceValue($model): ?string
    {
        $columns = $model->slugable ?? ['full_name', 'name', 'title'];
        foreach ($columns as $col) {
            if (! empty($model->{$col})) {
                return $model->{$col};
            }
        }

        return null;
    }

    protected static function shouldGenerateSlugOnCreate($model): bool
    {
        return static::modelHasSlugAttribute($model)
            && empty($model->slug)
            && ! empty(static::getSlugSourceValue($model));
    }

    protected static function shouldGenerateSlugOnUpdate($model): bool
    {
        $cols = $model->slugable ?? ['full_name', 'name', 'title'];
        foreach ($cols as $col) {
            if ($model->isDirty($col) && ! $model->isDirty('slug')) {
                return true;
            }
        }

        return false;
    }

    protected static function modelHasSlugAttribute($model): bool
    {
        return in_array('slug', $model->getFillable(), true)
            || array_key_exists('slug', $model->getAttributes());
    }

    protected static function generateUniqueSlug($model, string $value): string
    {
        $base = Str::slug($value);
        $slug = $base;
        $i = 1;

        while (
            $model->newQuery()
                ->where('slug', $slug)
                ->when($model->getKey(), function ($q) use ($model) {
                    $q->where($model->getKeyName(), '!=', $model->getKey());
                })
                ->exists()
        ) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}

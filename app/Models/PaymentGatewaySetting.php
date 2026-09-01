<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\GatewayType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class PaymentGatewaySetting extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'is_active',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public static function findBySlug(string $slug): ?self
    {
        return static::where('slug', $slug)->first();
    }

    public static function getActiveSettings(string $slug): ?array
    {
        $cacheKey = "payment_gateway_{$slug}";

        return Cache::remember($cacheKey, 3600, function () use ($slug): ?array {
            $record = static::active()->where('slug', $slug)->first();

            return $record?->settings;
        });
    }

    public static function clearCache(?string $slug = null): void
    {
        if ($slug !== null) {
            Cache::forget("payment_gateway_{$slug}");

            return;
        }

        foreach (GatewayType::cases() as $case) {
            Cache::forget("payment_gateway_{$case->value}");
        }
    }

    public function getSetting(string $key, mixed $default = null): mixed
    {
        return data_get($this->settings, $key, $default);
    }
}

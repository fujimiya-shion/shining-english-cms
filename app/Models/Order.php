<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Traits\CanReorder;
use BackedEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use CanReorder, HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'total_amount',
        'status',
        'payment_method',
        'payment_reference',
        'payment_checkout_url',
        'payment_metadata',
        'paid_at',
        'placed_at',
        'order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_amount' => 'integer',
            'payment_metadata' => 'array',
            'paid_at' => 'datetime',
            'placed_at' => 'datetime',
            'status' => OrderStatus::class,
            'payment_method' => PaymentMethod::class,
        ];
    }

    public function setStatusAttribute(OrderStatus|string $status): void
    {
        $normalizedStatus = $status instanceof BackedEnum
            ? $status->value
            : strtolower(trim($status));

        $this->attributes['status'] = $normalizedStatus;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }
}

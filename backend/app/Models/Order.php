<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['number', 'user_id', 'status', 'payment_method', 'payment_status', 'customer_name', 'phone', 'email', 'governorate_id', 'shipping_address', 'subtotal_piasters', 'discount_piasters', 'credit_piasters', 'shipping_piasters', 'gift_piasters', 'total_piasters', 'coupon_id', 'coupon_code', 'customer_note', 'reserved_until', 'placed_at'])]
class Order extends Model
{
    public const STATUSES = ['pending_payment', 'placed', 'confirmed', 'preparing', 'shipped', 'delivered', 'cancelled', 'returned'];

    /** Allowed transitions; anything else is refused by OrderStatusService. */
    public const TRANSITIONS = [
        'pending_payment' => ['placed', 'cancelled'],
        'placed' => ['confirmed', 'cancelled'],
        'confirmed' => ['preparing', 'cancelled'],
        'preparing' => ['shipped', 'cancelled'],
        'shipped' => ['delivered', 'returned'],
        'delivered' => ['returned'],
        'cancelled' => [],
        'returned' => [],
    ];

    protected function casts(): array
    {
        return ['shipping_address' => 'array', 'reserved_until' => 'datetime', 'placed_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorate::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function gift(): HasOne
    {
        return $this->hasOne(OrderGift::class);
    }

    public function history(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->orderBy('id');
    }
}

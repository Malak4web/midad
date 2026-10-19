<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'type', 'value', 'min_subtotal_piasters', 'max_discount_piasters', 'usage_limit', 'per_customer_limit', 'kind', 'phone', 'source_order_id', 'starts_at', 'ends_at', 'is_active'])]
class Coupon extends Model
{
    public const TYPE_PERCENT = 'percent';

    public const TYPE_FIXED = 'fixed';

    public const TYPE_FREE_SHIPPING = 'free_shipping';

    public const KIND_DISCOVERY_CREDIT = 'discovery_credit';

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'starts_at' => 'datetime', 'ends_at' => 'datetime', 'value' => 'integer', 'used_count' => 'integer'];
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(CouponRedemption::class);
    }
}

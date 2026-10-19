<?php

namespace App\Services\Cart;

use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Support\Money;

/** Coupon rules (BE-33). Returns a customer-facing Arabic reason, or null when the coupon applies. */
final class CouponService
{
    public static function find(string $code): ?Coupon
    {
        return Coupon::where('code', strtoupper(trim($code)))->first();
    }

    public static function problem(Coupon $c, int $subtotalPiasters, ?string $phone = null, ?int $userId = null): ?string
    {
        if (! $c->is_active) {
            return 'هذا الكود غير مفعّل.';
        }
        if ($c->starts_at && $c->starts_at->isFuture()) {
            return 'هذا الكود لم يبدأ بعد.';
        }
        if ($c->ends_at && $c->ends_at->isPast()) {
            return 'انتهت صلاحية هذا الكود.';
        }
        if ($c->usage_limit !== null && $c->used_count >= $c->usage_limit) {
            return 'تم استخدام هذا الكود بالكامل.';
        }
        if ($subtotalPiasters < $c->min_subtotal_piasters) {
            return 'هذا الكود يعمل مع الطلبات من '.Money::egp($c->min_subtotal_piasters).' ج.م فأكثر.';
        }
        if ($c->kind === Coupon::KIND_DISCOVERY_CREDIT && $phone && $c->phone && $c->phone !== $phone) {
            return 'رصيد مجموعة الاكتشاف مرتبط برقم موبايل آخر.';
        }
        if ($c->per_customer_limit !== null && ($phone || $userId)) {
            $used = CouponRedemption::where('coupon_id', $c->id)
                ->where(fn ($q) => $q->when($phone, fn ($x) => $x->orWhere('phone', $phone))->when($userId, fn ($x) => $x->orWhere('user_id', $userId)))
                ->count();
            if ($used >= $c->per_customer_limit) {
                return 'استخدمت هذا الكود من قبل.';
            }
        }

        return null;
    }

    public static function label(Coupon $c): string
    {
        return match ($c->type) {
            Coupon::TYPE_PERCENT => "خصم {$c->value}٪",
            Coupon::TYPE_FREE_SHIPPING => 'شحن مجاني',
            default => $c->kind === Coupon::KIND_DISCOVERY_CREDIT
                ? 'رصيد مجموعة الاكتشاف'
                : 'خصم '.Money::egp($c->value).' ج.م',
        };
    }
}

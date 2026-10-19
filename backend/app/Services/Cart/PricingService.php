<?php

namespace App\Services\Cart;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\Governorate;
use App\Models\Product;
use App\Models\Setting;

/**
 * The only place prices are computed (BE-32). Every number comes from the
 * database; nothing the browser sends is trusted (TST-30). All values in piasters.
 */
final class PricingService
{
    public static function quote(Cart $cart, ?Governorate $gov = null, ?string $phone = null, ?int $userId = null): array
    {
        $cart->loadMissing(['items.variant.product', 'coupon']);
        $box = Setting::get('discovery_box', []);
        $boxPrice = (int) ($box['price_piasters'] ?? 0);
        $sampleIds = $cart->items->where('kind', 'discovery_box')->flatMap(fn ($i) => $i->meta['product_ids'] ?? [])->unique();
        $sampleNames = $sampleIds->isEmpty() ? collect() : Product::whereIn('id', $sampleIds)->pluck('name', 'id');

        $lines = $cart->items->map(function (CartItem $i) use ($boxPrice, $sampleNames) {
            if ($i->kind === 'discovery_box') {
                return [
                    'item' => $i, 'kind' => 'discovery_box', 'unit' => $boxPrice, 'quantity' => $i->quantity,
                    'total' => $boxPrice * $i->quantity, 'available' => true,
                    'samples' => collect($i->meta['product_ids'] ?? [])->map(fn ($id) => $sampleNames[$id] ?? null)->filter()->values()->all(),
                ];
            }
            $v = $i->variant;
            $ok = $v && $v->is_active && $v->product && $v->product->is_active && ! $v->product->trashed();

            return [
                'item' => $i, 'kind' => 'product', 'variant' => $v, 'unit' => $ok ? $v->price_piasters : 0,
                'quantity' => $i->quantity, 'total' => $ok ? $v->price_piasters * $i->quantity : 0,
                'available' => $ok && $v->available() >= $i->quantity, 'stock' => $ok ? $v->available() : 0,
            ];
        });

        $subtotal = (int) $lines->sum('total');
        $productSubtotal = (int) $lines->where('kind', 'product')->sum('total');
        $discount = 0;
        $credit = 0;
        $freeShipping = false;
        $coupon = $cart->coupon;
        $couponProblem = $coupon ? CouponService::problem($coupon, $subtotal, $phone, $userId) : null;
        if ($coupon && ! $couponProblem) {
            match (true) {
                $coupon->type === Coupon::TYPE_FREE_SHIPPING => $freeShipping = true,
                $coupon->kind === Coupon::KIND_DISCOVERY_CREDIT => $credit = min($coupon->value, $productSubtotal),
                $coupon->type === Coupon::TYPE_PERCENT => $discount = min(
                    intdiv($subtotal * min($coupon->value, 100), 100),
                    $coupon->max_discount_piasters ?? PHP_INT_MAX,
                ),
                default => $discount = min($coupon->value, $subtotal),
            };
        }

        $threshold = (int) Setting::get('free_shipping_threshold_piasters', 0);
        $afterDiscounts = max(0, $subtotal - $discount - $credit);
        $shipping = null;
        if ($gov) {
            $shipping = ($freeShipping || ($threshold > 0 && $afterDiscounts >= $threshold) || $lines->isEmpty()) ? 0 : $gov->shipping_fee_piasters;
        }
        $gift = $cart->gift_wrap ? (int) Setting::get('gift_wrap_fee_piasters', 0) : 0;

        return [
            'lines' => $lines,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'credit' => $credit,
            'shipping' => $shipping,
            'gift' => $gift,
            'total' => $afterDiscounts + ($shipping ?? 0) + $gift,
            'coupon' => $coupon,
            'couponProblem' => $couponProblem,
            'freeShippingThreshold' => $threshold,
            'freeShippingRemaining' => $threshold > 0 ? max(0, $threshold - $afterDiscounts) : null,
        ];
    }
}

<?php

namespace App\Services\Orders;

use App\Events\OrderStatusChanged;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\Governorate;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\User;
use App\Services\Cart\PricingService;
use App\Support\Phone;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Turns a cart into an order inside one transaction (BE-35, BE-36). */
final class OrderService
{
    public function __construct(private OrderStatusService $status) {}

    public function place(Cart $cart, array $data, ?User $user): Order
    {
        $phone = Phone::normalize($data['phone']);
        $gov = Governorate::where('is_active', true)->findOrFail($data['governorate_id']);
        $online = in_array($data['payment_method'], ['card', 'wallet'], true);

        $order = DB::transaction(function () use ($cart, $data, $user, $phone, $gov, $online) {
            $cart = Cart::whereKey($cart->id)->lockForUpdate()->firstOrFail();
            $cart->load(['items.variant.product', 'coupon']);
            if ($cart->items->isEmpty()) {
                throw ValidationException::withMessages(['cart' => ['السلة فارغة.']]);
            }

            // lock every variant row so two checkouts cannot sell the last bottle twice (TST-05)
            $variantIds = $cart->items->where('kind', 'product')->pluck('product_variant_id')->filter()->unique()->sort()->values();
            $variants = ProductVariant::with('product')->whereIn('id', $variantIds)->lockForUpdate()->get()->keyBy('id');
            foreach ($cart->items->where('kind', 'product') as $item) {
                $v = $variants[$item->product_variant_id] ?? null;
                if (! $v || ! $v->is_active || ! $v->product?->is_active || $v->available() < $item->quantity) {
                    $name = $item->variant?->product?->name ?? 'عطر';
                    throw ValidationException::withMessages(['cart' => ["{$name} ({$item->variant?->size_ml} مل) لم يعد متوفراً بالكمية المطلوبة. عدّل السلة وحاول مرة أخرى."]]);
                }
            }

            $q = PricingService::quote($cart, $gov, $phone, $user?->id);
            if ($q['coupon'] && $q['couponProblem']) {
                throw ValidationException::withMessages(['code' => [$q['couponProblem']]]);
            }

            $order = Order::create([
                'number' => $this->number(),
                'user_id' => $user?->id,
                'status' => $online ? 'pending_payment' : 'placed',
                'payment_method' => $data['payment_method'],
                'payment_status' => $online ? 'pending' : 'unpaid',
                'customer_name' => trim(strip_tags($data['customer_name'])),
                'phone' => $phone,
                'email' => isset($data['email']) ? strtolower($data['email']) : $user?->email,
                'governorate_id' => $gov->id,
                'shipping_address' => [
                    'governorate' => $gov->name,
                    'city' => isset($data['city']) ? strip_tags($data['city']) : null,
                    'street' => strip_tags($data['street']),
                    'building' => isset($data['building']) ? strip_tags($data['building']) : null,
                    'floor' => isset($data['floor']) ? strip_tags($data['floor']) : null,
                    'apartment' => isset($data['apartment']) ? strip_tags($data['apartment']) : null,
                    'landmark' => isset($data['landmark']) ? strip_tags($data['landmark']) : null,
                ],
                'subtotal_piasters' => $q['subtotal'],
                'discount_piasters' => $q['discount'],
                'credit_piasters' => $q['credit'],
                'shipping_piasters' => $q['shipping'] ?? 0,
                'gift_piasters' => $q['gift'],
                'total_piasters' => $q['total'],
                'coupon_id' => $q['coupon']?->id,
                'coupon_code' => $q['coupon']?->code,
                'customer_note' => isset($data['note']) ? mb_substr(strip_tags($data['note']), 0, 500) : null,
                'reserved_until' => $online ? now()->addMinutes((int) Setting::get('reservation_minutes', 30)) : null,
                'placed_at' => now(),
            ]);

            foreach ($q['lines'] as $l) {
                if ($l['kind'] === 'discovery_box') {
                    $line = $order->items()->create([
                        'kind' => 'discovery_box', 'product_name' => 'مجموعة الاكتشاف',
                        'unit_price_piasters' => $l['unit'], 'quantity' => $l['quantity'], 'line_total_piasters' => $l['total'],
                        'meta' => ['product_ids' => $l['item']->meta['product_ids'] ?? []],
                    ]);
                    foreach ($l['item']->meta['product_ids'] ?? [] as $i => $pid) {
                        $line->selections()->create(['product_id' => $pid, 'sort' => $i]);
                    }

                    continue;
                }
                $v = $variants[$l['variant']->id];
                $order->items()->create([
                    'kind' => 'product', 'product_variant_id' => $v->id, 'product_name' => $v->product->name, 'size_ml' => $v->size_ml,
                    'unit_price_piasters' => $l['unit'], 'quantity' => $l['quantity'], 'line_total_piasters' => $l['total'],
                ]);
                $v->increment('reserved', $l['quantity']);
            }

            if ($cart->gift_wrap) {
                $order->gift()->create(['wrap' => true, 'recipient_name' => $cart->gift_recipient, 'message' => $cart->gift_message]);
            }
            if ($q['coupon'] && ($q['discount'] || $q['credit'] || $q['coupon']->type === Coupon::TYPE_FREE_SHIPPING)) {
                CouponRedemption::create(['coupon_id' => $q['coupon']->id, 'order_id' => $order->id, 'user_id' => $user?->id, 'phone' => $phone, 'amount_piasters' => $q['discount'] + $q['credit']]);
                $q['coupon']->increment('used_count');
            }
            $this->status->record($order, null, $order->status, $user, 'تم إنشاء الطلب');

            $cart->items()->delete();
            $cart->update(['coupon_id' => null, 'gift_wrap' => false, 'gift_recipient' => null, 'gift_message' => null]);

            return $order;
        });

        // COD orders are placed immediately; online ones announce themselves once paid.
        if ($order->status === 'placed') {
            event(new OrderStatusChanged($order, null, 'placed'));
        }

        return $order;
    }

    private function number(): string
    {
        do {
            $n = 'MD-'.now()->format('ymd').'-'.random_int(1000, 9999);
        } while (Order::where('number', $n)->exists());

        return $n;
    }
}

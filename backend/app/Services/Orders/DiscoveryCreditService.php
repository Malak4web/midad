<?php

namespace App\Services\Orders;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\Setting;
use Illuminate\Support\Str;

/**
 * When a discovery box is delivered, its price comes back to the customer as a
 * one-use credit on their first bottle, bound to the order's phone (BE-45).
 */
final class DiscoveryCreditService
{
    public static function issue(Order $order): ?Coupon
    {
        $boxes = $order->items()->where('kind', 'discovery_box')->get();
        if ($boxes->isEmpty() || Coupon::where('source_order_id', $order->id)->exists()) {
            return null;
        }
        $days = (int) (Setting::get('discovery_box', [])['credit_days'] ?? 60);

        return Coupon::create([
            'code' => 'MDK-'.Str::upper(Str::random(6)),
            'type' => Coupon::TYPE_FIXED,
            'kind' => Coupon::KIND_DISCOVERY_CREDIT,
            'value' => (int) $boxes->sum('line_total_piasters'),
            'usage_limit' => 1,
            'per_customer_limit' => 1,
            'phone' => $order->phone,
            'source_order_id' => $order->id,
            'starts_at' => now(),
            'ends_at' => now()->addDays($days),
            'is_active' => true,
        ]);
    }
}

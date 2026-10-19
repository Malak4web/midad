<?php

namespace App\Services\Orders;

use App\Events\OrderStatusChanged;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The order state machine (Order::TRANSITIONS) and the stock it moves.
 * Stock: placing reserves; confirming turns the reservation into a sale;
 * cancelling releases it (or restocks if already sold); a return restocks.
 */
final class OrderStatusService
{
    public const LABELS = [
        'pending_payment' => 'بانتظار الدفع',
        'placed' => 'تم استلام الطلب',
        'confirmed' => 'تم التأكيد',
        'preparing' => 'قيد التجهيز',
        'shipped' => 'تم الشحن',
        'delivered' => 'تم التوصيل',
        'cancelled' => 'ملغي',
        'returned' => 'مرتجع',
    ];

    public function transition(Order $order, string $to, ?User $actor = null, ?string $note = null): Order
    {
        return DB::transaction(function () use ($order, $to, $actor, $note) {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $from = $order->status;
            if (! in_array($to, Order::TRANSITIONS[$from] ?? [], true)) {
                throw ValidationException::withMessages(['status' => ['لا يمكن نقل الطلب من «'.self::LABELS[$from].'» إلى «'.(self::LABELS[$to] ?? $to).'».']]);
            }
            $sold = in_array($from, ['confirmed', 'preparing', 'shipped', 'delivered'], true);

            foreach ($order->items()->where('kind', 'product')->get() as $item) {
                $v = $item->product_variant_id ? ProductVariant::whereKey($item->product_variant_id)->lockForUpdate()->first() : null;
                if (! $v) {
                    continue;
                }
                if ($to === 'confirmed') {
                    $v->forceFill(['stock' => max(0, $v->stock - $item->quantity), 'reserved' => max(0, $v->reserved - $item->quantity)])->save();
                    $this->move($v, -$item->quantity, 'sale', $order, $actor);
                } elseif ($to === 'cancelled' && ! $sold) {
                    $v->forceFill(['reserved' => max(0, $v->reserved - $item->quantity)])->save();
                } elseif (($to === 'cancelled' && $sold) || $to === 'returned') {
                    $v->increment('stock', $item->quantity);
                    $this->move($v, $item->quantity, $to === 'returned' ? 'return' : 'release', $order, $actor);
                }
                if ($to === 'delivered') {
                    $v->product()->increment('sold_count', $item->quantity);
                }
            }

            $order->update(['status' => $to] + ($to === 'cancelled' && $order->payment_status === 'pending' ? ['payment_status' => 'failed'] : []));
            $this->record($order, $from, $to, $actor, $note);
            event(new OrderStatusChanged($order, $from, $to));

            return $order;
        });
    }

    public function record(Order $order, ?string $from, string $to, ?User $actor, ?string $note): void
    {
        $order->history()->create(['from_status' => $from, 'to_status' => $to, 'user_id' => $actor?->id, 'note' => $note]);
    }

    private function move(ProductVariant $v, int $change, string $reason, Order $order, ?User $actor): void
    {
        StockMovement::create(['product_variant_id' => $v->id, 'change' => $change, 'reason' => $reason, 'order_id' => $order->id, 'user_id' => $actor?->id]);
    }
}

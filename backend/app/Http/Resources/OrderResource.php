<?php

namespace App\Http\Resources;

use App\Models\Order;
use App\Services\Orders\OrderStatusService;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Order */
class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'number' => $this->number,
            'status' => $this->status,
            'statusLabel' => OrderStatusService::LABELS[$this->status] ?? $this->status,
            'paymentMethod' => $this->payment_method,
            'paymentStatus' => $this->payment_status,
            'canRetryPayment' => $this->status === 'pending_payment' && $this->payment_status !== 'paid' && $this->reserved_until?->isFuture(),
            'reservedUntil' => $this->reserved_until?->toIso8601String(),
            'customerName' => $this->customer_name,
            'phone' => $this->phone,
            'shippingAddress' => $this->shipping_address,
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($i) => [
                'kind' => $i->kind,
                'name' => $i->product_name,
                'ml' => $i->size_ml,
                'quantity' => $i->quantity,
                'unitPrice' => Money::egp($i->unit_price_piasters),
                'lineTotal' => Money::egp($i->line_total_piasters),
                'samples' => $i->relationLoaded('selections') ? $i->selections->map(fn ($s) => $s->product?->name)->filter()->values() : [],
            ])),
            'gift' => $this->whenLoaded('gift', fn () => $this->gift ? ['recipient' => $this->gift->recipient_name, 'message' => $this->gift->message] : null),
            'totals' => [
                'subtotal' => Money::egp($this->subtotal_piasters),
                'discount' => Money::egp($this->discount_piasters),
                'credit' => Money::egp($this->credit_piasters),
                'shipping' => Money::egp($this->shipping_piasters),
                'gift' => Money::egp($this->gift_piasters),
                'total' => Money::egp($this->total_piasters),
            ],
            'couponCode' => $this->coupon_code,
            'history' => $this->whenLoaded('history', fn () => $this->history->map(fn ($h) => [
                'status' => $h->to_status,
                'label' => OrderStatusService::LABELS[$h->to_status] ?? $h->to_status,
                'at' => $h->created_at?->toIso8601String(),
            ])),
            'placedAt' => $this->placed_at?->toIso8601String(),
        ];
    }
}

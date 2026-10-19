<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\Orders\OrderStatusService;
use App\Support\Money;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** Orders desk: filter, inspect, move through states, note, refund, print (BE-43, BE-49, BE-50). */
class OrderController extends Controller
{
    public function __construct(private OrderStatusService $status) {}

    public function index(Request $request): JsonResponse
    {
        $f = $request->validate([
            'status' => ['nullable', 'in:'.implode(',', Order::STATUSES)],
            'payment_status' => ['nullable', 'in:unpaid,pending,paid,failed,refunded'],
            'payment_method' => ['nullable', 'in:cod,card,wallet'],
            'governorate_id' => ['nullable', 'integer'],
            'from' => ['nullable', 'date'], 'to' => ['nullable', 'date'],
            'q' => ['nullable', 'string', 'max:60'],
            'page' => ['nullable', 'integer'],
        ]);
        $orders = Order::query()->with('governorate')->withCount('items')
            ->when($f['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($f['payment_status'] ?? null, fn ($q, $v) => $q->where('payment_status', $v))
            ->when($f['payment_method'] ?? null, fn ($q, $v) => $q->where('payment_method', $v))
            ->when($f['governorate_id'] ?? null, fn ($q, $v) => $q->where('governorate_id', $v))
            ->when($f['from'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($f['to'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->when($f['q'] ?? null, fn ($q, $v) => $q->where(fn ($x) => $x->where('number', 'like', '%'.strtoupper($v).'%')->orWhere('phone', 'like', "%{$v}%")->orWhere('customer_name', 'like', "%{$v}%")))
            ->latest('id')->paginate(25);

        $counts = Order::query()->selectRaw('status, count(*) c')->groupBy('status')->pluck('c', 'status');

        return response()->json([
            'data' => $orders->getCollection()->map(fn (Order $o) => [
                'number' => $o->number, 'status' => $o->status, 'statusLabel' => OrderStatusService::LABELS[$o->status],
                'customer' => $o->customer_name, 'phone' => $o->phone, 'governorate' => $o->governorate?->name,
                'paymentMethod' => $o->payment_method, 'paymentStatus' => $o->payment_status,
                'total' => Money::egp($o->total_piasters), 'items' => $o->items_count, 'createdAt' => $o->created_at?->toIso8601String(),
            ]),
            'meta' => ['total' => $orders->total(), 'current_page' => $orders->currentPage(), 'last_page' => $orders->lastPage(), 'counts' => $counts],
        ]);
    }

    public function show(Order $order): JsonResponse
    {
        $order->load(['items.selections.product', 'gift', 'history.user', 'payments', 'user']);
        $base = (new OrderResource($order))->resolve();

        return response()->json(['data' => array_merge($base, [
            'email' => $order->email,
            'customerNote' => $order->customer_note,
            'internalNote' => $order->internal_note,
            'allowed' => array_values(Order::TRANSITIONS[$order->status] ?? []),
            'payments' => $order->payments->map(fn ($p) => ['gateway' => $p->gateway, 'reference' => $p->gateway_reference, 'transactionId' => $p->transaction_id, 'amount' => Money::egp($p->amount_piasters), 'status' => $p->status, 'at' => $p->processed_at?->toIso8601String()]),
            'history' => $order->history->map(fn ($h) => ['status' => $h->to_status, 'label' => OrderStatusService::LABELS[$h->to_status] ?? $h->to_status, 'note' => $h->note, 'by' => $h->user?->name, 'at' => $h->created_at?->toIso8601String()]),
            'account' => $order->user ? ['id' => $order->user->id, 'name' => $order->user->name] : null,
        ])]);
    }

    public function transition(Request $request, Order $order): JsonResponse
    {
        $data = $request->validate(['status' => ['required', 'in:'.implode(',', Order::STATUSES)], 'note' => ['nullable', 'string', 'max:255']]);
        $this->status->transition($order, $data['status'], $request->user(), $data['note'] ?? null);

        return $this->show($order->fresh());
    }

    public function note(Request $request, Order $order): JsonResponse
    {
        $data = $request->validate(['internal_note' => ['present', 'nullable', 'string', 'max:2000']]);
        // staff-only field, deliberately outside $fillable
        $order->forceFill(['internal_note' => $data['internal_note']])->save();

        return $this->show($order);
    }

    /** Manual refund record (BE-43): money goes back through Paymob's dashboard, this keeps the books straight. */
    public function refund(Request $request, Order $order): JsonResponse
    {
        $data = $request->validate(['note' => ['required', 'string', 'max:255']]);
        DB::transaction(function () use ($order, $data, $request) {
            $paid = $order->payments()->where('status', 'paid')->latest('id')->lockForUpdate()->first();
            if (! $paid) {
                throw ValidationException::withMessages(['refund' => ['لا يوجد دفع إلكتروني مكتمل لهذا الطلب.']]);
            }
            $paid->update(['status' => 'refunded']);
            $order->update(['payment_status' => 'refunded']);
            $this->status->record($order, $order->status, $order->status, $request->user(), 'استرداد: '.$data['note']);
        });

        return $this->show($order->fresh());
    }

    /** Printable waybill (BE-50): plain HTML the browser prints, no PDF library. */
    public function waybill(Order $order): View
    {
        return view('admin.waybill', ['order' => $order->load(['items', 'gift', 'governorate'])]);
    }
}

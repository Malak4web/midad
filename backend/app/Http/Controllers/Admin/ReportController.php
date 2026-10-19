<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivity;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Dashboard tiles, period reports and CSV exports in plain PHP (BE-53). */
class ReportController extends Controller
{
    /** Orders that count as sales: placed and onward, never cancelled or unpaid online. */
    private const SOLD = ['placed', 'confirmed', 'preparing', 'shipped', 'delivered'];

    public function dashboard(): JsonResponse
    {
        $today = CarbonImmutable::today();
        $sum = fn ($from) => Order::whereIn('status', self::SOLD)->where('created_at', '>=', $from);

        return response()->json(['data' => [
            'today' => ['orders' => $sum($today)->count(), 'sales' => Money::egp((int) $sum($today)->sum('total_piasters'))],
            'week' => ['orders' => $sum($today->subDays(6))->count(), 'sales' => Money::egp((int) $sum($today->subDays(6))->sum('total_piasters'))],
            'toConfirm' => Order::where('status', 'placed')->count(),
            'toShip' => Order::whereIn('status', ['confirmed', 'preparing'])->count(),
            'awaitingPayment' => Order::where('status', 'pending_payment')->count(),
            'lowStock' => ProductVariant::where('is_active', true)->whereRaw('stock - reserved <= low_stock_threshold')->count(),
            'latest' => Order::latest('id')->limit(8)->get()->map(fn ($o) => ['number' => $o->number, 'customer' => $o->customer_name, 'status' => $o->status, 'total' => Money::egp($o->total_piasters), 'at' => $o->created_at?->toIso8601String()]),
            'activity' => AdminActivity::with('user')->latest('id')->limit(8)->get()->map(fn ($a) => ['who' => $a->user?->name, 'action' => $a->action, 'at' => $a->created_at?->toIso8601String()]),
        ]]);
    }

    public function report(Request $request): JsonResponse
    {
        [$from, $to] = $this->range($request);
        // columns are table-qualified: the governorate breakdown joins this query
        $orders = Order::whereIn('orders.status', self::SOLD)->whereBetween('orders.created_at', [$from, $to]);

        $daily = (clone $orders)->selectRaw('DATE(orders.created_at) d, COUNT(*) n, SUM(orders.total_piasters) s')->groupBy('d')->orderBy('d')->get()->keyBy('d');
        $days = [];
        for ($d = $from; $d <= $to; $d = $d->addDay()) {
            $row = $daily[$d->toDateString()] ?? null;
            $days[] = ['date' => $d->toDateString(), 'orders' => (int) ($row->n ?? 0), 'sales' => Money::egp((int) ($row->s ?? 0))];
        }

        $top = OrderItem::query()->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereIn('orders.status', self::SOLD)->whereBetween('orders.created_at', [$from, $to])->where('order_items.kind', 'product')
            ->selectRaw('order_items.product_name name, order_items.size_ml ml, SUM(order_items.quantity) qty, SUM(order_items.line_total_piasters) total')
            ->groupBy('order_items.product_name', 'order_items.size_ml')->orderByDesc('qty')->limit(10)->get()
            ->map(fn ($r) => ['name' => $r->name, 'ml' => (int) $r->ml, 'qty' => (int) $r->qty, 'total' => Money::egp((int) $r->total)]);

        $govs = (clone $orders)->join('governorates', 'governorates.id', '=', 'orders.governorate_id')
            ->selectRaw('governorates.name name, COUNT(*) n, SUM(orders.total_piasters) s')->groupBy('governorates.name')->orderByDesc('n')->get()
            ->map(fn ($r) => ['name' => $r->name, 'orders' => (int) $r->n, 'sales' => Money::egp((int) $r->s)]);

        $methods = (clone $orders)->selectRaw('orders.payment_method m, COUNT(*) n, SUM(orders.total_piasters) s')->groupBy('m')->get()
            ->map(fn ($r) => ['method' => $r->m, 'orders' => (int) $r->n, 'sales' => Money::egp((int) $r->s)]);

        return response()->json(['data' => [
            'from' => $from->toDateString(), 'to' => $to->toDateString(),
            'totals' => ['orders' => (clone $orders)->count(), 'sales' => Money::egp((int) (clone $orders)->sum('total_piasters')), 'average' => Money::egp((int) (clone $orders)->avg('total_piasters'))],
            'daily' => $days, 'topProducts' => $top, 'governorates' => $govs, 'paymentMethods' => $methods,
        ]]);
    }

    /** CSV of orders in the period; UTF-8 BOM so Excel shows Arabic correctly. */
    public function exportOrders(Request $request): StreamedResponse
    {
        [$from, $to] = $this->range($request);

        return response()->streamDownload(function () use ($from, $to) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['رقم الطلب', 'التاريخ', 'الحالة', 'العميل', 'الموبايل', 'المحافظة', 'الدفع', 'حالة الدفع', 'المجموع الفرعي', 'الخصم', 'الشحن', 'الإجمالي']);
            Order::with('governorate')->whereBetween('created_at', [$from, $to])->orderBy('id')->chunk(500, function ($orders) use ($out) {
                foreach ($orders as $o) {
                    fputcsv($out, [
                        $o->number, $o->created_at?->format('Y-m-d H:i'), $o->status, $this->safe($o->customer_name), $o->phone, $o->governorate?->name,
                        $o->payment_method, $o->payment_status, Money::egp($o->subtotal_piasters), Money::egp($o->discount_piasters + $o->credit_piasters),
                        Money::egp($o->shipping_piasters), Money::egp($o->total_piasters),
                    ]);
                }
            });
            fclose($out);
        }, "midad-orders-{$from->toDateString()}-{$to->toDateString()}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function range(Request $request): array
    {
        $d = $request->validate(['from' => ['nullable', 'date'], 'to' => ['nullable', 'date', 'after_or_equal:from']]);
        $to = isset($d['to']) ? CarbonImmutable::parse($d['to'])->endOfDay() : CarbonImmutable::now()->endOfDay();
        $from = isset($d['from']) ? CarbonImmutable::parse($d['from'])->startOfDay() : $to->subDays(29)->startOfDay();
        if ($from->diffInDays($to) > 366) {
            $from = $to->subDays(366)->startOfDay();
        }

        return [$from, $to];
    }

    /** Neutralizes spreadsheet formulas in user-typed cells (CSV injection). */
    private function safe(?string $v): ?string
    {
        return $v !== null && preg_match('/^[=+\-@\t\r]/', $v) ? "'".$v : $v;
    }
}

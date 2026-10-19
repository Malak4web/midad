<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Governorate;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Cart\CartService;
use App\Services\Orders\OrderService;
use App\Services\Payments\FakeGateway;
use App\Services\Payments\PaymentService;
use App\Services\Payments\PaymobHmac;
use App\Support\Money;
use App\Support\Phone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/** Checkout, order status, tracking and payment callbacks (BE-35 … BE-42). */
class OrderController extends Controller
{
    public function __construct(private CartService $carts, private OrderService $orders, private PaymentService $payments) {}

    public function governorates(): JsonResponse
    {
        $list = Governorate::where('is_active', true)->orderBy('sort')->get()
            ->map(fn ($g) => ['id' => $g->id, 'name' => $g->name, 'fee' => Money::egp($g->shipping_fee_piasters), 'days' => [$g->delivery_days_min, $g->delivery_days_max]]);

        return response()->json(['data' => $list]);
    }

    public function checkout(Request $request): JsonResponse
    {
        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', Phone::rule()],
            'email' => ['nullable', 'email', 'max:190'],
            'governorate_id' => ['required', 'integer', 'exists:governorates,id'],
            // the address is one line the customer writes; the old parts stay optional for API callers
            'city' => ['nullable', 'string', 'max:80'],
            'street' => ['required', 'string', 'max:300'],
            'building' => ['nullable', 'string', 'max:40'],
            'floor' => ['nullable', 'string', 'max:20'],
            'apartment' => ['nullable', 'string', 'max:20'],
            'landmark' => ['nullable', 'string', 'max:160'],
            'note' => ['nullable', 'string', 'max:500'],
            'payment_method' => ['required', 'in:cod,card,wallet'],
        ]);
        $cart = $this->carts->resolve($request);
        if (! $cart) {
            throw ValidationException::withMessages(['cart' => ['السلة فارغة.']]);
        }
        $order = $this->orders->place($cart, $data, $request->user());
        $redirect = $order->status === 'pending_payment' ? $this->payments->start($order) : null;

        return response()->json(['data' => ['number' => $order->number, 'phone' => $order->phone, 'status' => $order->status, 'redirectUrl' => $redirect]], 201);
    }

    /** Order page for guests (number + phone) and owners (BE-38, BE-42). */
    public function show(Request $request, string $number): OrderResource
    {
        return new OrderResource($this->find($request, $number)->load(['items.selections.product', 'gift', 'history']));
    }

    public function retryPayment(Request $request, string $number): JsonResponse
    {
        $order = $this->find($request, $number);
        if ($order->status !== 'pending_payment' || $order->payment_status === 'paid' || ! $order->reserved_until?->isFuture()) {
            throw ValidationException::withMessages(['order' => ['لا يمكن الدفع لهذا الطلب الآن.']]);
        }

        return response()->json(['data' => ['redirectUrl' => $this->payments->start($order)]]);
    }

    /** Paymob server-to-server callback (BE-41). */
    public function paymobWebhook(Request $request): JsonResponse
    {
        $ok = $this->payments->handleCallback((array) $request->input('obj', []), $request->query('hmac'));

        return response()->json(['received' => $ok], $ok ? 200 : 400);
    }

    /** Local sandbox standing in for the Paymob page until the account exists. */
    public function sandbox(string $reference): JsonResponse
    {
        abort_unless(config('services.payments.driver') === 'fake' && ! app()->isProduction(), 404);
        $payment = Payment::where('gateway_reference', $reference)->with('order')->firstOrFail();

        return response()->json(['data' => [
            'reference' => $payment->gateway_reference, 'status' => $payment->status, 'amount' => Money::egp($payment->amount_piasters),
            'orderNumber' => $payment->order->number, 'phone' => $payment->order->phone,
        ]]);
    }

    public function sandboxComplete(Request $request, string $reference): JsonResponse
    {
        abort_unless(config('services.payments.driver') === 'fake' && ! app()->isProduction(), 404);
        $data = $request->validate(['result' => ['required', 'in:success,fail']]);
        $payment = Payment::where('gateway_reference', $reference)->with('order')->firstOrFail();
        $obj = FakeGateway::transaction($payment, $data['result'] === 'success');
        $this->payments->handleCallback($obj, PaymobHmac::sign($obj, (string) config('services.paymob.hmac_secret')));

        return response()->json(['data' => ['orderNumber' => $payment->order->number, 'phone' => $payment->order->phone]]);
    }

    private function find(Request $request, string $number): Order
    {
        $order = Order::where('number', $number)->firstOrFail();
        $owner = $request->user() && $order->user_id === $request->user()->id;
        $phone = $request->input('phone');
        // guests prove ownership with the phone used on the order; wrong pairs look like "not found" (TST-26)
        abort_unless($owner || ($phone && Phone::normalize($phone) === $order->phone), 404);

        return $order;
    }
}

<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\Payment;
use App\Services\Orders\OrderStatusService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/** Starts payments and applies verified gateway callbacks (BE-39 … BE-41). */
final class PaymentService
{
    public function __construct(private OrderStatusService $status) {}

    public function gateway(): PaymentGateway
    {
        return config('services.payments.driver') === 'paymob' ? new PaymobGateway : new FakeGateway;
    }

    /** Creates a pending payment for an online order and returns the redirect URL. */
    public function start(Order $order): string
    {
        $gateway = $this->gateway();
        $payment = $order->payments()->create([
            'gateway' => $gateway->name(),
            'gateway_reference' => $order->number.'-'.Str::upper(Str::random(6)),
            'amount_piasters' => $order->total_piasters,
            'status' => 'pending',
        ]);

        return $gateway->start($order->loadMissing('items'), $payment);
    }

    /**
     * Applies a Paymob transaction callback. Idempotent: the same transaction id
     * is processed once (TST-06). Returns false when the signature is wrong.
     */
    public function handleCallback(array $obj, ?string $hmac): bool
    {
        if (! PaymobHmac::valid($obj, $hmac, (string) config('services.paymob.hmac_secret'))) {
            Log::warning('payments.webhook.bad_hmac', ['ref' => data_get($obj, 'order.merchant_order_id')]);

            return false;
        }
        $txId = (string) data_get($obj, 'id');
        $reference = (string) data_get($obj, 'order.merchant_order_id');

        DB::transaction(function () use ($obj, $txId, $reference) {
            if (Payment::where('transaction_id', $txId)->exists()) {
                return; // already processed
            }
            $payment = Payment::where('gateway_reference', $reference)->lockForUpdate()->first();
            if (! $payment || $payment->status === 'paid') {
                return;
            }
            $paid = data_get($obj, 'success') === true && data_get($obj, 'pending') === false
                && (int) data_get($obj, 'amount_cents') === $payment->amount_piasters;

            $payment->update([
                'transaction_id' => $txId,
                'status' => $paid ? 'paid' : 'failed',
                'payload' => ['transaction' => $obj],
                'processed_at' => now(),
            ]);
            $order = Order::whereKey($payment->order_id)->lockForUpdate()->first();
            if ($paid) {
                $order->update(['payment_status' => 'paid', 'reserved_until' => null]);
                if ($order->status === 'pending_payment') {
                    $this->status->transition($order, 'placed', null, 'تم الدفع إلكترونياً');
                }
            } elseif ($order->payment_status !== 'paid') {
                $order->update(['payment_status' => 'failed']);
            }
        });

        return true;
    }
}

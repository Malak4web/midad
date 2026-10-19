<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\Payment;

/**
 * Local stand-in for Paymob until the account exists (SET-06). The shopper lands
 * on the SPA sandbox page (/pay/sandbox) which reports success or failure through
 * the same signed webhook path a real Paymob callback uses.
 */
final class FakeGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'fake';
    }

    public function start(Order $order, Payment $payment): string
    {
        return rtrim(config('app.frontend_url'), '/').'/pay/sandbox?ref='.urlencode($payment->gateway_reference);
    }

    /** Builds a Paymob-shaped transaction object, as Paymob would POST it. */
    public static function transaction(Payment $payment, bool $success): array
    {
        return [
            'id' => random_int(100000000, 999999999),
            'amount_cents' => $payment->amount_piasters,
            'created_at' => now()->toIso8601String(),
            'currency' => 'EGP',
            'error_occured' => false,
            'has_parent_transaction' => false,
            'integration_id' => 0,
            'is_3d_secure' => true,
            'is_auth' => false,
            'is_capture' => false,
            'is_refunded' => false,
            'is_standalone_payment' => true,
            'is_voided' => false,
            'order' => ['id' => $payment->id, 'merchant_order_id' => $payment->gateway_reference],
            'owner' => 0,
            'pending' => false,
            'source_data' => ['pan' => '2346', 'sub_type' => 'MasterCard', 'type' => 'card'],
            'success' => $success,
        ];
    }
}

<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Paymob Unified Checkout through plain HTTP, no SDK (BE-40).
 * Creates an Intention and returns the hosted checkout URL.
 */
final class PaymobGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'paymob';
    }

    public function start(Order $order, Payment $payment): string
    {
        $cfg = config('services.paymob');
        $integration = $cfg['integrations'][$order->payment_method] ?? null;
        if (! $cfg['secret_key'] || ! $cfg['public_key'] || ! $integration) {
            throw new RuntimeException('Paymob is not configured.');
        }
        $address = $order->shipping_address;
        [$first, $last] = array_pad(explode(' ', $order->customer_name, 2), 2, '-');

        $res = Http::withHeaders(['Authorization' => 'Token '.$cfg['secret_key']])
            ->acceptJson()->timeout(20)->retry(2, 500, throw: false)
            ->post(rtrim($cfg['base_url'], '/').'/v1/intention/', [
                'amount' => $payment->amount_piasters,
                'currency' => 'EGP',
                'payment_methods' => [(int) $integration],
                'special_reference' => $payment->gateway_reference,
                'notification_url' => url('/api/payments/paymob/webhook'),
                'redirection_url' => rtrim(config('app.frontend_url'), '/').'/order/'.$order->number.'?phone='.$order->phone,
                'items' => $order->items->map(fn ($i) => ['name' => $i->product_name.($i->size_ml ? " {$i->size_ml}ml" : ''), 'amount' => $i->unit_price_piasters, 'quantity' => $i->quantity])->values()->all(),
                'billing_data' => [
                    'first_name' => $first, 'last_name' => $last, 'phone_number' => '+2'.$order->phone, 'email' => $order->email ?: 'na@midad.local',
                    'street' => $address['street'] ?? 'NA', 'building' => $address['building'] ?? 'NA', 'floor' => $address['floor'] ?? 'NA',
                    'apartment' => $address['apartment'] ?? 'NA', 'city' => $address['city'] ?? 'NA', 'state' => $address['governorate'] ?? 'NA', 'country' => 'EG',
                ],
            ]);
        if (! $res->successful() || ! $res->json('client_secret')) {
            throw new RuntimeException('Paymob intention failed: '.$res->status());
        }
        $payment->update(['payload' => ['intention_id' => $res->json('id')]]);

        return rtrim($cfg['base_url'], '/').'/unifiedcheckout/?publicKey='.urlencode($cfg['public_key']).'&clientSecret='.urlencode($res->json('client_secret'));
    }
}

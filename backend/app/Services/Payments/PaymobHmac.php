<?php

namespace App\Services\Payments;

/**
 * Paymob "transaction processed" callback HMAC: SHA-512 over these fields of the
 * transaction object, concatenated in this exact order (Paymob docs).
 */
final class PaymobHmac
{
    private const FIELDS = [
        'amount_cents', 'created_at', 'currency', 'error_occured', 'has_parent_transaction', 'id',
        'integration_id', 'is_3d_secure', 'is_auth', 'is_capture', 'is_refunded', 'is_standalone_payment',
        'is_voided', 'order.id', 'owner', 'pending', 'source_data.pan', 'source_data.sub_type',
        'source_data.type', 'success',
    ];

    public static function sign(array $obj, string $secret): string
    {
        $concat = '';
        foreach (self::FIELDS as $field) {
            $value = data_get($obj, $field);
            $concat .= is_bool($value) ? ($value ? 'true' : 'false') : (string) $value;
        }

        return hash_hmac('sha512', $concat, $secret);
    }

    public static function valid(array $obj, ?string $hmac, string $secret): bool
    {
        return $hmac !== null && $secret !== '' && hash_equals(self::sign($obj, $secret), strtolower($hmac));
    }
}

<?php

namespace App\Support;

use Closure;

/** Egyptian mobile numbers: 010 / 011 / 012 / 015 + 8 digits. */
final class Phone
{
    /** Stores every number as 01XXXXXXXXX; accepts Arabic-Indic digits, spaces, dashes and +20. */
    public static function normalize(string $phone): string
    {
        $phone = strtr($phone, ['٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9']);
        $digits = preg_replace('/\D+/', '', $phone);
        if (str_starts_with($digits, '20') && strlen($digits) === 12) {
            $digits = substr($digits, 2);
        }
        if (strlen($digits) === 10 && $digits[0] === '1') {
            $digits = '0'.$digits;
        }

        return $digits;
    }

    public static function valid(string $phone): bool
    {
        return (bool) preg_match('/^01[0125][0-9]{8}$/', self::normalize($phone));
    }

    /** Validation rule: checks the number after normalization. */
    public static function rule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) {
            if (! is_string($value) || ! self::valid($value)) {
                $fail('اكتب رقم موبايل مصري صحيح من ١١ رقماً يبدأ بـ 01.');
            }
        };
    }
}

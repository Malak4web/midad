<?php

namespace App\Support;

/** Money is stored in piasters; the API speaks whole Egyptian pounds where possible. */
final class Money
{
    public static function egp(?int $piasters): int|float|null
    {
        if ($piasters === null) {
            return null;
        }
        $pounds = $piasters / 100;

        return fmod($pounds, 1.0) === 0.0 ? (int) $pounds : round($pounds, 2);
    }

    public static function piasters(int|float|string $pounds): int
    {
        return (int) round(((float) $pounds) * 100);
    }
}

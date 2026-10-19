<?php

namespace App\Support;

/**
 * Arabic search normalization (BE-15). Mirrors frontend/src/utils/arabic.js so
 * the suggestions the visitor sees match what the API returns.
 */
final class ArabicNormalizer
{
    public static function normalize(?string $text): string
    {
        $text = (string) $text;
        // diacritics (tashkeel), superscript alef, Quranic marks, tatweel
        $text = preg_replace('/[\x{0610}-\x{061A}\x{064B}-\x{065F}\x{0670}\x{06D6}-\x{06ED}\x{0640}]/u', '', $text);
        $text = strtr($text, [
            'إ' => 'ا', 'أ' => 'ا', 'آ' => 'ا', 'ٱ' => 'ا',
            'ى' => 'ي', 'ة' => 'ه', 'ؤ' => 'و', 'ئ' => 'ي',
        ]);
        $text = mb_strtolower($text, 'UTF-8');

        return trim(preg_replace('/\s+/u', ' ', $text));
    }

    /** Builds the searchable blob stored in products.search_text. */
    public static function searchBlob(array $parts): string
    {
        return self::normalize(implode(' ', array_filter($parts)));
    }
}

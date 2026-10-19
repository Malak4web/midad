<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/** Unique slugs from Latin or Arabic text (BE-18). */
final class Slugger
{
    /** @param class-string<Model> $model */
    public static function unique(string $model, string $source, ?int $ignoreId = null, string $column = 'slug'): string
    {
        // Latin text → ascii slug; Arabic text stays Arabic (readable URLs, no transliteration)
        $base = preg_match('/[A-Za-z]/', $source)
            ? Str::slug($source)
            : trim(preg_replace('/[^\p{Arabic}\p{N}]+/u', '-', ArabicNormalizer::normalize($source)), '-');
        $base = $base !== '' ? Str::limit($base, 100, '') : 'item';

        $slug = $base;
        $i = 2;
        while ($model::withoutGlobalScopes()->where($column, $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}

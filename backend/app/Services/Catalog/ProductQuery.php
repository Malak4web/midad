<?php

namespace App\Services\Catalog;

use App\Models\Product;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;

/**
 * Hand-written catalog filters (BE-17): category, note, price range, size, sort.
 * Every input is whitelisted; nothing from the request reaches SQL unchecked.
 */
final class ProductQuery
{
    public const SORTS = ['default', 'best', 'new', 'price_asc', 'price_desc'];

    public static function base(): Builder
    {
        return Product::query()->active()->with(['category', 'variants', 'notes', 'images']);
    }

    public static function apply(Builder $q, array $f): Builder
    {
        if (! empty($f['category'])) {
            $q->whereHas('category', fn ($c) => $c->where('slug', $f['category'])->where('is_active', true));
        }
        if (! empty($f['note'])) {
            $q->whereHas('notes', fn ($n) => $n->where('notes.slug', $f['note']));
        }
        $size = isset($f['size']) ? (int) $f['size'] : null;
        $min = isset($f['price_min']) ? Money::piasters($f['price_min']) : null;
        $max = isset($f['price_max']) ? Money::piasters($f['price_max']) : null;
        if ($size || $min !== null || $max !== null) {
            $q->whereHas('variants', function ($v) use ($size, $min, $max) {
                $v->where('is_active', true)
                    ->when($size, fn ($x) => $x->where('size_ml', $size))
                    ->when($min !== null, fn ($x) => $x->where('price_piasters', '>=', $min))
                    ->when($max !== null, fn ($x) => $x->where('price_piasters', '<=', $max));
            });
        }

        $fromPrice = '(select min(price_piasters) from product_variants where product_variants.product_id = products.id and product_variants.is_active = 1)';

        return match ($f['sort'] ?? 'default') {
            'best' => $q->orderByDesc('sold_count')->orderBy('sort'),
            'new' => $q->orderByDesc('created_at')->orderByDesc('id'),
            'price_asc' => $q->orderByRaw("$fromPrice asc")->orderBy('sort'),
            'price_desc' => $q->orderByRaw("$fromPrice desc")->orderBy('sort'),
            default => $q->orderBy('sort')->orderBy('id'),
        };
    }

    public static function rules(): array
    {
        return [
            'category' => ['nullable', 'string', 'max:64'],
            'note' => ['nullable', 'string', 'max:64'],
            'size' => ['nullable', 'integer', 'in:10,30,50,100'],
            'price_min' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'price_max' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'sort' => ['nullable', 'in:'.implode(',', self::SORTS)],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:48'],
        ];
    }
}

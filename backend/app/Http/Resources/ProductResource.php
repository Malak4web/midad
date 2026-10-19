<?php

namespace App\Http\Resources;

use App\Models\Product;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Product card shape. Matches the frontend mock (src/data/catalog.js) so the
 * SPA can switch from mock to API without touching components.
 *
 * @mixin Product
 */
class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $variants = $this->whenLoaded('variants', fn () => $this->variants->where('is_active', true)->values());

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'category' => $this->whenLoaded('category', fn () => $this->category->slug),
            'name' => $this->name,
            'latin' => $this->latin_name,
            'inspiredBy' => $this->inspired_by,
            'signature' => $this->signature_note,
            'concentration' => $this->concentration,
            'notes' => $this->whenLoaded('notes', fn () => $this->notes->where('pivot.layer', 'key')->pluck('name')->values()),
            'variants' => $this->whenLoaded('variants', fn () => $variants->map(fn ($v) => [
                'ml' => $v->size_ml,
                'price' => Money::egp($v->price_piasters),
                'compareAt' => Money::egp($v->compare_at_piasters),
                'stock' => $v->available(),
            ])),
            'fromPrice' => $this->whenLoaded('variants', fn () => Money::egp($variants->min('price_piasters'))),
            'image' => $this->whenLoaded('images', fn () => $this->images->first()?->media()),
        ];
    }
}

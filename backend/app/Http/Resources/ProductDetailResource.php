<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;

/** @mixin Product */
class ProductDetailResource extends ProductResource
{
    public function toArray(Request $request): array
    {
        return parent::toArray($request) + [
            'description' => $this->description,
            'pyramid' => self::pyramid($this->resource),
            'images' => $this->whenLoaded('images', fn () => $this->images->map->media()->values()),
        ];
    }

    /** top/heart/base from the pivot layers; null when the pyramid is not entered yet. */
    public static function pyramid($product): ?array
    {
        $notes = $product->relationLoaded('notes') ? $product->notes : $product->notes()->get();
        $layers = [];
        foreach (['top', 'heart', 'base'] as $layer) {
            $layers[$layer] = $notes->where('pivot.layer', $layer)->sortBy('pivot.sort')->pluck('name')->values()->all();
        }

        return array_filter($layers) ? $layers : null;
    }
}

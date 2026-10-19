<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['size_ml', 'sku', 'price_piasters', 'compare_at_piasters', 'stock', 'low_stock_threshold', 'is_active'])]
class ProductVariant extends Model
{
    public const SIZES = [10, 30, 50, 100];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'price_piasters' => 'integer', 'compare_at_piasters' => 'integer', 'stock' => 'integer', 'reserved' => 'integer'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function available(): int
    {
        return max(0, $this->stock - $this->reserved);
    }
}

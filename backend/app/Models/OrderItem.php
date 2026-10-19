<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['kind', 'product_variant_id', 'product_name', 'size_ml', 'unit_price_piasters', 'quantity', 'line_total_piasters', 'meta'])]
class OrderItem extends Model
{
    protected function casts(): array
    {
        return ['meta' => 'array'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function selections(): HasMany
    {
        return $this->hasMany(DiscoveryBoxSelection::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['category_id', 'product_id', 'tagline', 'palette', 'texture', 'ingredients', 'scene_image', 'bottle_image', 'is_sample', 'is_active', 'sort', 'starts_at', 'ends_at'])]
class CategorySpotlight extends Model
{
    public const TEXTURES = ['plain', 'resin', 'petals', 'smoke'];

    protected function casts(): array
    {
        return ['palette' => 'array', 'ingredients' => 'array', 'is_sample' => 'boolean', 'is_active' => 'boolean', 'starts_at' => 'datetime', 'ends_at' => 'datetime'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}

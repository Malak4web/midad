<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['slug', 'name', 'latin_name', 'description', 'is_active', 'sort'])]
class Category extends Model
{
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function spotlights(): HasMany
    {
        return $this->hasMany(CategorySpotlight::class);
    }

    /** The spotlight live right now, if any. */
    public function activeSpotlight(): ?CategorySpotlight
    {
        return $this->spotlights()
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->orderBy('sort')
            ->first();
    }
}

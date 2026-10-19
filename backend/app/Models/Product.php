<?php

namespace App\Models;

use App\Support\ArabicNormalizer;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['category_id', 'slug', 'name', 'latin_name', 'inspired_by', 'signature_note', 'description', 'concentration', 'is_active', 'sort'])]
class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'sold_count' => 'integer'];
    }

    protected static function booted(): void
    {
        // search_text is derived, never written directly
        static::saving(function (Product $p) {
            $p->search_text = ArabicNormalizer::searchBlob([$p->name, $p->latin_name, $p->inspired_by, $p->signature_note]);
        });
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('size_ml');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort');
    }

    public function notes(): BelongsToMany
    {
        return $this->belongsToMany(Note::class, 'product_note')->withPivot(['layer', 'sort'])->orderByPivot('sort');
    }
}

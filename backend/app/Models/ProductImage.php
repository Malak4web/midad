<?php

namespace App\Models;

use App\Services\Media\ImageService;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['path', 'alt', 'sort'])]
class ProductImage extends Model
{
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** INT-12: the storefront's view of one image — 800px src plus every rendition. */
    public function media(): array
    {
        $base = preg_replace('/-\d+\.webp$/', '', $this->path);

        return [
            'src' => '/storage/'.$this->path,
            'srcset' => implode(', ', array_map(fn ($w) => "/storage/{$base}-{$w}.webp {$w}w", ImageService::WIDTHS)),
            'alt' => (string) $this->alt,
        ];
    }
}

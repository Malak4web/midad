<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name', 'slug'])]
class Note extends Model
{
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_note')->withPivot(['layer', 'sort']);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['key', 'question', 'sort', 'is_active'])]
class FinderQuestion extends Model
{
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function answers(): HasMany
    {
        return $this->hasMany(FinderAnswer::class)->orderBy('sort');
    }
}

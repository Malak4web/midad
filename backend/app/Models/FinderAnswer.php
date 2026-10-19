<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['value', 'label', 'sort'])]
class FinderAnswer extends Model
{
    public $timestamps = false;

    public function question(): BelongsTo
    {
        return $this->belongsTo(FinderQuestion::class, 'finder_question_id');
    }

    public function weights(): HasMany
    {
        return $this->hasMany(FinderAnswerWeight::class);
    }
}

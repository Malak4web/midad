<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['target_type', 'target_id', 'weight'])]
class FinderAnswerWeight extends Model
{
    public $timestamps = false;

    public function answer(): BelongsTo
    {
        return $this->belongsTo(FinderAnswer::class, 'finder_answer_id');
    }
}

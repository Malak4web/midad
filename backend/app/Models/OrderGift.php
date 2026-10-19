<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['wrap', 'recipient_name', 'message'])]
class OrderGift extends Model
{
    protected $primaryKey = 'order_id';

    public $incrementing = false;

    public $timestamps = false;

    protected function casts(): array
    {
        return ['wrap' => 'boolean'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}

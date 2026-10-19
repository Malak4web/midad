<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['gateway', 'gateway_reference', 'transaction_id', 'amount_piasters', 'currency', 'status', 'payload', 'processed_at'])]
class Payment extends Model
{
    protected function casts(): array
    {
        return ['payload' => 'array', 'processed_at' => 'datetime', 'amount_piasters' => 'integer'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}

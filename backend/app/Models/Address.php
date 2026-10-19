<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['governorate_id', 'recipient_name', 'phone', 'city', 'street', 'building', 'floor', 'apartment', 'landmark', 'is_default'])]
class Address extends Model
{
    /** The account API returns addresses as they are; keep internals out of it. */
    protected $hidden = ['user_id', 'created_at', 'updated_at'];

    protected function casts(): array
    {
        return ['is_default' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorate::class);
    }
}

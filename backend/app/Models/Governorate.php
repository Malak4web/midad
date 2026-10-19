<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'shipping_fee_piasters', 'delivery_days_min', 'delivery_days_max', 'is_active', 'sort'])]
class Governorate extends Model
{
    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'shipping_fee_piasters' => 'integer'];
    }
}

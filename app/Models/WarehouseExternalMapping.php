<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'warehouse_id',
    'external_system',
    'external_warehouse_id',
    'external_name',
    'external_type',
    'is_active',
])]
class WarehouseExternalMapping extends Model
{
    protected function casts(): array
    {
        return [
            'external_warehouse_id' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
}

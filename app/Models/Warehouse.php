<?php

namespace App\Models;

use App\Enums\WarehouseType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'company_id',
    'branch_id',
    'code',
    'name',
    'type',
    'is_general',
    'address',
    'is_active',
])]
class Warehouse extends Model
{
    protected function casts(): array
    {
        return [
            'type' => WarehouseType::class,
            'is_general' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function externalMappings(): HasMany
    {
        return $this->hasMany(WarehouseExternalMapping::class);
    }

    public function morfeusMappings(): HasMany
    {
        return $this->externalMappings()->where('external_system', 'morfeus');
    }
}

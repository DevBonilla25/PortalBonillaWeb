<?php

namespace App\Models;

use App\Enums\VehicleStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'company_id',
    'code',
    'plate',
    'brand',
    'model',
    'type',
    'capacity_kg',
    'volume_m3',
    'status',
    'is_active',
    'observations',
])]
class Vehicle extends Model
{
    protected function casts(): array
    {
        return [
            'capacity_kg' => 'decimal:2',
            'volume_m3' => 'decimal:2',
            'status' => VehicleStatus::class,
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function driverProfiles(): HasMany
    {
        return $this->hasMany(DriverProfile::class, 'default_vehicle_id');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'current_vehicle_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(TicketAssignment::class);
    }
}

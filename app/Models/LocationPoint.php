<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'ticket_id',
    'driver_id',
    'vehicle_id',
    'latitude',
    'longitude',
    'accuracy',
    'speed',
    'heading',
    'battery_level',
    'recorded_at',
    'received_at',
    'source',
    'local_event_id',
    'metadata',
])]
class LocationPoint extends Model
{
    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'accuracy' => 'decimal:2',
            'speed' => 'decimal:2',
            'heading' => 'decimal:2',
            'recorded_at' => 'datetime',
            'received_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(DriverProfile::class, 'driver_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}

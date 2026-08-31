<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'driver_profile_id',
    'ticket_id',
    'pickup_order_id',
    'type',
    'title',
    'body',
    'data',
    'sent_at',
    'read_at',
])]
class DriverNotification extends Model
{
    protected function casts(): array
    {
        return [
            'data' => 'array',
            'sent_at' => 'datetime',
            'read_at' => 'datetime',
        ];
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(DriverProfile::class, 'driver_profile_id');
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function pickupOrder(): BelongsTo
    {
        return $this->belongsTo(PickupOrder::class);
    }
}

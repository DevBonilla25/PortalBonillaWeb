<?php

namespace App\Models;

use App\Enums\TicketEventType;
use App\Enums\TicketStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'ticket_id',
    'user_id',
    'driver_id',
    'event_type',
    'previous_status',
    'new_status',
    'latitude',
    'longitude',
    'accuracy',
    'occurred_at',
    'received_at',
    'source',
    'connection_status',
    'local_event_id',
    'description',
    'metadata',
])]
class TicketEvent extends Model
{
    protected function casts(): array
    {
        return [
            'event_type' => TicketEventType::class,
            'previous_status' => TicketStatus::class,
            'new_status' => TicketStatus::class,
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'accuracy' => 'decimal:2',
            'occurred_at' => 'datetime',
            'received_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(DriverProfile::class, 'driver_id');
    }
}

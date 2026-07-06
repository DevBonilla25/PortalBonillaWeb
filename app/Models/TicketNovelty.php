<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'ticket_id',
    'reported_by',
    'driver_id',
    'novelty_reason_id',
    'novelty_type',
    'description',
    'photo_path',
    'status',
    'latitude',
    'longitude',
    'accuracy',
    'occurred_at',
])]
class TicketNovelty extends Model
{
    protected $table = 'ticket_novelties';

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'accuracy' => 'decimal:2',
            'occurred_at' => 'datetime',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(DriverProfile::class, 'driver_id');
    }

    public function reason(): BelongsTo
    {
        return $this->belongsTo(NoveltyReason::class, 'novelty_reason_id');
    }
}

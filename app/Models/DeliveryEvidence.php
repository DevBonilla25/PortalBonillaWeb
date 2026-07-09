<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable([
    'ticket_id',
    'driver_id',
    'received_by_name',
    'received_by_identification',
    'photo_path',
    'signature_path',
    'observation',
    'latitude',
    'longitude',
    'accuracy',
    'occurred_at',
])]
class DeliveryEvidence extends Model
{
    protected $table = 'delivery_evidences';

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

    public function driver(): BelongsTo
    {
        return $this->belongsTo(DriverProfile::class, 'driver_id');
    }

    public function mediaAttachments(): MorphMany
    {
        return $this->morphMany(MediaAttachment::class, 'attachable');
    }
}

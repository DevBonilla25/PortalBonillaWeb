<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'ticket_id',
    'product_code',
    'product_name',
    'quantity',
    'loaded_quantity',
    'is_loaded',
    'load_reviewed_by',
    'load_reviewed_at',
    'load_observation',
    'unit',
    'observations',
])]
class TicketItem extends Model
{
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'loaded_quantity' => 'decimal:2',
            'is_loaded' => 'boolean',
            'load_reviewed_at' => 'datetime',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function loadReviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'load_reviewed_by');
    }

    public function isLoadReviewed(): bool
    {
        return $this->load_reviewed_at !== null;
    }
}

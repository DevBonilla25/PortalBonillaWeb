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
    'unit',
    'observations',
])]
class TicketItem extends Model
{
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }
}

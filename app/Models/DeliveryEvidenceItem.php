<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['delivery_evidence_id', 'ticket_item_id', 'delivered_quantity'])]
class DeliveryEvidenceItem extends Model
{
    protected function casts(): array
    {
        return ['delivered_quantity' => 'decimal:2'];
    }

    public function evidence(): BelongsTo
    {
        return $this->belongsTo(DeliveryEvidence::class, 'delivery_evidence_id');
    }

    public function ticketItem(): BelongsTo
    {
        return $this->belongsTo(TicketItem::class);
    }
}

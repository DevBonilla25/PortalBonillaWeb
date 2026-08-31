<?php

namespace App\Models;

use App\Enums\PickupOrderAction;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['pickup_order_id', 'performed_by', 'action', 'from_status', 'to_status', 'notes', 'latitude', 'longitude', 'accuracy', 'location_source', 'occurred_at', 'received_at', 'local_event_id'])]
class PickupOrderEvent extends Model
{
    protected function casts(): array
    {
        return ['action' => PickupOrderAction::class, 'latitude' => 'decimal:7', 'longitude' => 'decimal:7', 'accuracy' => 'decimal:2', 'occurred_at' => 'datetime', 'received_at' => 'datetime'];
    }

    public function pickupOrder(): BelongsTo
    {
        return $this->belongsTo(PickupOrder::class);
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}

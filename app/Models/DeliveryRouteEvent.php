<?php

namespace App\Models;

use App\Enums\DeliveryRouteEventType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['delivery_route_id', 'performed_by', 'type', 'from_status', 'to_status', 'notes', 'latitude', 'longitude', 'accuracy', 'location_source', 'recorded_at', 'received_at', 'local_event_id'])]
class DeliveryRouteEvent extends Model
{
    protected function casts(): array
    {
        return ['type' => DeliveryRouteEventType::class, 'latitude' => 'decimal:7', 'longitude' => 'decimal:7', 'accuracy' => 'decimal:2', 'recorded_at' => 'datetime', 'received_at' => 'datetime'];
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(DeliveryRoute::class, 'delivery_route_id');
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}

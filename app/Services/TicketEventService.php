<?php

namespace App\Services;

use App\Enums\TicketEventType;
use App\Enums\TicketStatus;
use App\Models\DriverProfile;
use App\Models\Ticket;
use App\Models\TicketEvent;
use App\Models\User;
use Illuminate\Support\Carbon;

class TicketEventService
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function record(
        Ticket $ticket,
        TicketEventType $eventType,
        ?User $user = null,
        ?TicketStatus $previousStatus = null,
        ?TicketStatus $newStatus = null,
        ?DriverProfile $driver = null,
        ?string $description = null,
        array $metadata = [],
        ?float $latitude = null,
        ?float $longitude = null,
        ?float $accuracy = null,
        ?Carbon $occurredAt = null,
        ?string $source = null,
        ?string $connectionStatus = null,
        ?string $localEventId = null,
    ): TicketEvent {
        return $ticket->events()->create([
            'user_id' => $user?->id,
            'driver_id' => $driver?->id,
            'event_type' => $eventType,
            'previous_status' => $previousStatus,
            'new_status' => $newStatus,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'accuracy' => $accuracy,
            'occurred_at' => $occurredAt ?? now(),
            'received_at' => now(),
            'source' => $source ?? 'web',
            'connection_status' => $connectionStatus,
            'local_event_id' => $localEventId,
            'description' => $description,
            'metadata' => $metadata === [] ? null : $metadata,
        ]);
    }
}

<?php

namespace App\Services;

use App\Enums\TicketEventType;
use App\Enums\TicketStatus;
use App\Models\DriverProfile;
use App\Models\Ticket;
use App\Models\TicketEvent;
use App\Models\User;

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
    ): TicketEvent {
        return $ticket->events()->create([
            'user_id' => $user?->id,
            'driver_id' => $driver?->id,
            'event_type' => $eventType,
            'previous_status' => $previousStatus,
            'new_status' => $newStatus,
            'occurred_at' => now(),
            'received_at' => now(),
            'source' => 'web',
            'description' => $description,
            'metadata' => $metadata === [] ? null : $metadata,
        ]);
    }
}

<?php

namespace App\Support\Tickets;

use App\Enums\TicketStatus;
use App\Models\Ticket;

class TicketAssignmentForm
{
    /**
     * @return array{
     *     driver_id: int|null,
     *     vehicle_id: int|null,
     *     warehouse_user_id: int|null,
     *     assistant_ids: list<int>,
     *     internal_observation: string|null,
     * }
     */
    public static function defaultState(Ticket $ticket): array
    {
        if ($ticket->status === TicketStatus::PendingReassignment) {
            return [
                'driver_id' => null,
                'vehicle_id' => null,
                'warehouse_user_id' => null,
                'assistant_ids' => [],
                'internal_observation' => null,
            ];
        }

        $ticket->loadMissing(['latestAssignment.assistants']);

        $assignment = $ticket->latestAssignment;

        return [
            'driver_id' => $assignment?->driver_id ?? $ticket->current_driver_id,
            'vehicle_id' => $assignment?->vehicle_id ?? $ticket->current_vehicle_id,
            'warehouse_user_id' => $assignment?->warehouse_user_id,
            'assistant_ids' => $assignment?->assistants->pluck('id')->all(),
            'internal_observation' => $assignment?->internal_observation,
        ];
    }

    public static function hasExistingAssignment(Ticket $ticket): bool
    {
        if ($ticket->status === TicketStatus::PendingReassignment) {
            return false;
        }

        $ticket->loadMissing('latestAssignment');

        return $ticket->latestAssignment !== null
            || $ticket->current_driver_id !== null
            || $ticket->current_vehicle_id !== null;
    }
}

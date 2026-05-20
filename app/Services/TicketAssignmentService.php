<?php

namespace App\Services;

use App\Enums\TicketAssignmentStatus;
use App\Models\Ticket;
use App\Models\TicketAssignment;
use App\Models\User;

class TicketAssignmentService
{
    /**
     * @param  list<int>  $assistantIds
     */
    public function assign(
        Ticket $ticket,
        ?int $driverId,
        ?int $vehicleId,
        ?int $warehouseUserId,
        array $assistantIds,
        User $assignedBy,
        ?string $internalObservation = null,
    ): TicketAssignment {
        $ticket->assignments()
            ->where('status', TicketAssignmentStatus::Active)
            ->update(['status' => TicketAssignmentStatus::Replaced]);

        $assignment = $ticket->assignments()->create([
            'driver_id' => $driverId,
            'vehicle_id' => $vehicleId,
            'warehouse_user_id' => $warehouseUserId,
            'assigned_by' => $assignedBy->id,
            'assigned_at' => now(),
            'status' => TicketAssignmentStatus::Active,
            'internal_observation' => $internalObservation,
        ]);

        $assignment->assistants()->sync($assistantIds);

        $ticket->forceFill([
            'current_driver_id' => $driverId,
            'current_vehicle_id' => $vehicleId,
            'assigned_by' => $assignedBy->id,
            'assigned_at' => now(),
        ])->save();

        return $assignment->refresh();
    }
}

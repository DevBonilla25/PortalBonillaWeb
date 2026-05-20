<?php

namespace App\Actions\Tickets;

use App\Enums\TicketEventType;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketAssignmentService;
use App\Services\TicketEventService;
use App\Services\TicketWorkflowService;

class AssignTicketResourcesAction
{
    public function __construct(
        private readonly TicketAssignmentService $assignments,
        private readonly TicketWorkflowService $workflow,
        private readonly TicketEventService $events,
    ) {}

    /**
     * @param  list<int>  $assistantIds
     */
    public function execute(
        Ticket $ticket,
        ?int $driverId,
        ?int $vehicleId,
        ?int $warehouseUserId,
        array $assistantIds,
        User $assignedBy,
        ?string $internalObservation = null,
    ): Ticket {
        $previousStatus = $ticket->status;

        $assignment = $this->assignments->assign(
            ticket: $ticket,
            driverId: $driverId,
            vehicleId: $vehicleId,
            warehouseUserId: $warehouseUserId,
            assistantIds: $assistantIds,
            assignedBy: $assignedBy,
            internalObservation: $internalObservation,
        );

        $ticket = $ticket->refresh();

        if ($previousStatus === TicketStatus::SentToWarehouse) {
            $ticket = $this->workflow->transition($ticket, TicketStatus::AssignedToWarehouse);
        }

        $this->events->record(
            ticket: $ticket,
            eventType: TicketEventType::ResourcesAssigned,
            user: $assignedBy,
            previousStatus: $previousStatus,
            newStatus: $ticket->status,
            description: 'Recursos asignados al ticket.',
        );

        return $ticket;
    }
}

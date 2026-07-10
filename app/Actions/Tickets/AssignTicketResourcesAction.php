<?php

namespace App\Actions\Tickets;

use App\Enums\TicketEventType;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use App\Services\DriverPushNotificationService;
use App\Services\TicketAssignmentService;
use App\Services\TicketEventService;
use App\Services\TicketWorkflowService;

class AssignTicketResourcesAction
{
    public function __construct(
        private readonly TicketAssignmentService $assignments,
        private readonly TicketWorkflowService $workflow,
        private readonly TicketEventService $events,
        private readonly DriverPushNotificationService $driverPushNotifications,
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

        if (in_array($previousStatus, [TicketStatus::SentToWarehouse, TicketStatus::AssignedToWarehouse], true)) {
            $ticket = $this->workflow->transition($ticket, TicketStatus::Picking);
        }

        $this->events->record(
            ticket: $ticket,
            eventType: TicketEventType::ResourcesAssigned,
            user: $assignedBy,
            previousStatus: $previousStatus,
            newStatus: $ticket->status,
            description: 'Recursos asignados al ticket.',
        );

        if ($driverId) {
            $this->driverPushNotifications->sendTicketAssigned($ticket);
        }

        return $ticket;
    }
}

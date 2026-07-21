<?php

namespace App\Actions\Tickets;

use App\Enums\TicketEventType;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use App\Services\DriverPushNotificationService;
use App\Services\TicketEventService;
use App\Services\TicketWorkflowService;

class ChangeTicketStatusAction
{
    public function __construct(
        private readonly TicketWorkflowService $workflow,
        private readonly TicketEventService $events,
        private readonly DriverPushNotificationService $driverPushNotifications,
    ) {}

    public function execute(Ticket $ticket, TicketStatus $nextStatus, ?User $user = null, ?string $description = null): Ticket
    {
        $previousStatus = $ticket->status;
        $ticket = $this->workflow->transition($ticket, $nextStatus);

        $this->events->record(
            ticket: $ticket,
            eventType: TicketEventType::StatusChanged,
            user: $user,
            previousStatus: $previousStatus,
            newStatus: $nextStatus,
            description: $description ?? 'Cambio de estado del ticket.',
        );

        if ($nextStatus === TicketStatus::Dispatched) {
            $this->driverPushNotifications->sendTicketDispatched($ticket);
        }

        return $ticket;
    }
}

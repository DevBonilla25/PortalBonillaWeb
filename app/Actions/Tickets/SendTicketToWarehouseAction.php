<?php

namespace App\Actions\Tickets;

use App\Enums\TicketEventType;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketEventService;
use App\Services\TicketWorkflowService;

class SendTicketToWarehouseAction
{
    public function __construct(
        private readonly TicketWorkflowService $workflow,
        private readonly TicketEventService $events,
    ) {}

    public function execute(Ticket $ticket, User $user): Ticket
    {
        $previousStatus = $ticket->status;

        $ticket = $this->workflow->transition($ticket, TicketStatus::SentToWarehouse);

        $this->events->record(
            ticket: $ticket,
            eventType: TicketEventType::SentToWarehouse,
            user: $user,
            previousStatus: $previousStatus,
            newStatus: TicketStatus::SentToWarehouse,
            description: 'Ticket enviado a bodega.',
        );

        return $ticket;
    }
}

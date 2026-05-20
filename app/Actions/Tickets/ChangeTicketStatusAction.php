<?php

namespace App\Actions\Tickets;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Services\TicketWorkflowService;

class ChangeTicketStatusAction
{
    public function __construct(
        private readonly TicketWorkflowService $workflow,
    ) {}

    public function execute(Ticket $ticket, TicketStatus $nextStatus): Ticket
    {
        return $this->workflow->transition($ticket, $nextStatus);
    }
}

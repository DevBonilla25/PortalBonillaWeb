<?php

namespace App\Actions\Tickets;

use App\Enums\TicketAssignmentStatus;
use App\Enums\TicketEventType;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketEventService;
use App\Services\TicketWorkflowService;
use DomainException;
use Illuminate\Support\Facades\DB;

class PrepareTicketReassignmentAction
{
    public function __construct(private readonly TicketWorkflowService $workflow, private readonly TicketEventService $events) {}

    public function execute(Ticket $ticket, User $user, ?string $observation = null): Ticket
    {
        if ($ticket->status !== TicketStatus::ArrivedBack) {
            throw new DomainException('Primero debes confirmar la recepción del retorno en bodega.');
        }

        return DB::transaction(function () use ($ticket, $user, $observation): Ticket {
            $previousStatus = $ticket->status;
            $ticket->assignments()->where('status', TicketAssignmentStatus::Active)->update(['status' => TicketAssignmentStatus::Replaced]);
            $ticket->resetLoadingChecklist();
            $ticket->forceFill(['current_driver_id' => null, 'current_vehicle_id' => null])->save();
            $ticket = $this->workflow->transition($ticket, TicketStatus::PendingReassignment);
            $this->events->record(
                ticket: $ticket, eventType: TicketEventType::PendingReassignment, user: $user,
                previousStatus: $previousStatus, newStatus: TicketStatus::PendingReassignment,
                description: $observation ?: 'Ticket disponible para una nueva asignación.',
            );

            return $ticket;
        });
    }
}

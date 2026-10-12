<?php

namespace App\Actions\Tickets;

use App\Enums\TicketEventType;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketEventService;
use App\Services\TicketWorkflowService;
use DomainException;
use Illuminate\Support\Facades\DB;

class ReceiveTicketReturnAction
{
    public function __construct(
        private readonly TicketWorkflowService $workflow,
        private readonly TicketEventService $events,
        private readonly PrepareTicketReassignmentAction $prepareReassignment,
    ) {}

    public function execute(Ticket $ticket, User $receivedBy, ?string $observation = null): Ticket
    {
        if ($ticket->status !== TicketStatus::Returning) {
            throw new DomainException('Solo se puede recibir en bodega un ticket que esté en retorno.');
        }

        return DB::transaction(function () use ($ticket, $receivedBy, $observation): Ticket {
            $previousStatus = $ticket->status;
            $ticket = $this->workflow->transition($ticket, TicketStatus::ArrivedBack);
            $attempt = $ticket->deliveryAttempts()->where('status', 'failed_returning')->latest('id')->first();
            $attempt?->forceFill([
                'status' => 'returned_to_warehouse',
                'warehouse_received_at' => now(),
                'warehouse_received_by' => $receivedBy->id,
                'warehouse_observation' => $observation,
            ])->save();
            $this->events->record(
                ticket: $ticket, eventType: TicketEventType::ReturnReceived, user: $receivedBy,
                previousStatus: $previousStatus, newStatus: TicketStatus::ArrivedBack,
                description: $observation ?: 'Retorno recibido y verificado en bodega.',
                metadata: ['delivery_attempt_id' => $attempt?->id],
            );

            return $this->prepareReassignment->execute($ticket, $receivedBy, $observation);
        });
    }
}

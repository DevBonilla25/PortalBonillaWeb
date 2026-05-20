<?php

namespace App\Services;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use DomainException;

class TicketWorkflowService
{
    public function assertCanTransition(Ticket $ticket, TicketStatus $nextStatus): void
    {
        $currentStatus = $ticket->status instanceof TicketStatus
            ? $ticket->status
            : TicketStatus::from($ticket->status);

        if (! $currentStatus->canTransitionTo($nextStatus)) {
            throw new DomainException("No se puede cambiar el ticket de {$currentStatus->value} a {$nextStatus->value}.");
        }
    }

    public function transition(Ticket $ticket, TicketStatus $nextStatus): Ticket
    {
        $this->assertCanTransition($ticket, $nextStatus);

        $ticket->forceFill([
            'status' => $nextStatus,
            ...$this->timestampsFor($nextStatus),
        ])->save();

        return $ticket->refresh();
    }

    /**
     * @return array<string, mixed>
     */
    private function timestampsFor(TicketStatus $status): array
    {
        return match ($status) {
            TicketStatus::AssignedToWarehouse => ['assigned_at' => now()],
            TicketStatus::Dispatched => ['dispatched_at' => now()],
            TicketStatus::Delivered => ['delivered_at' => now(), 'closed_at' => now()],
            TicketStatus::Returning => ['returned_at' => now()],
            TicketStatus::ArrivedBack, TicketStatus::Cancelled => ['closed_at' => now()],
            default => [],
        };
    }
}

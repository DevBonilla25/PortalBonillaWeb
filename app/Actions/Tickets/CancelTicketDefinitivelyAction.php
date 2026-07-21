<?php

namespace App\Actions\Tickets;

use App\Enums\TicketAssignmentStatus;
use App\Enums\TicketEventType;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketEventService;
use DomainException;
use Illuminate\Support\Facades\DB;

class CancelTicketDefinitivelyAction
{
    public function __construct(private readonly TicketEventService $events) {}

    public function execute(Ticket $ticket, string $reason, User $user): Ticket
    {
        if (blank(trim($reason))) {
            throw new DomainException('El motivo de la cancelacion es obligatorio.');
        }

        return DB::transaction(function () use ($ticket, $reason, $user): Ticket {
            $ticket = Ticket::query()->lockForUpdate()->findOrFail($ticket->id);
            $previousStatus = $ticket->status;

            if (! in_array($previousStatus, RescheduleTicketAction::allowedStatuses(), true)) {
                throw new DomainException('El ticket ya no se puede cancelar en su estado actual.');
            }

            $ticket->assignments()->where('status', TicketAssignmentStatus::Active)->update(['status' => TicketAssignmentStatus::Cancelled]);
            $ticket->forceFill([
                'status' => TicketStatus::Cancelled,
                'current_driver_id' => null,
                'current_vehicle_id' => null,
                'assigned_by' => null,
                'assigned_at' => null,
                'reschedule_reason' => null,
                'cancelled_reason' => trim($reason),
                'closed_at' => now(),
            ])->save();

            $this->events->record(
                ticket: $ticket,
                eventType: TicketEventType::StatusChanged,
                user: $user,
                previousStatus: $previousStatus,
                newStatus: TicketStatus::Cancelled,
                description: 'Ticket cancelado definitivamente: '.trim($reason),
            );

            return $ticket->refresh();
        });
    }
}

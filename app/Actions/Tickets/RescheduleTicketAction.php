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

class RescheduleTicketAction
{
    public function __construct(private readonly TicketEventService $events) {}

    public function execute(Ticket $ticket, string $reason, User $user): Ticket
    {
        if (blank(trim($reason))) {
            throw new DomainException('El motivo de la reprogramacion es obligatorio.');
        }

        return DB::transaction(function () use ($ticket, $reason, $user): Ticket {
            $ticket = Ticket::query()->lockForUpdate()->findOrFail($ticket->id);
            $previousStatus = $ticket->status;

            if (! in_array($previousStatus, self::allowedStatuses(), true)) {
                throw new DomainException('El ticket ya no se puede reprogramar en su estado actual.');
            }

            $this->releaseResources($ticket);
            $ticket->resetLoadingChecklist();
            $ticket->forceFill([
                'status' => TicketStatus::SentToWarehouse,
                'rescheduled_count' => $ticket->rescheduled_count + 1,
                'last_rescheduled_at' => now(),
                'reschedule_reason' => trim($reason),
                'cancelled_reason' => null,
                'closed_at' => null,
                'dispatched_at' => null,
            ])->save();

            $this->events->record(
                ticket: $ticket,
                eventType: TicketEventType::StatusChanged,
                user: $user,
                previousStatus: $previousStatus,
                newStatus: TicketStatus::SentToWarehouse,
                description: 'Ticket reprogramado y enviado nuevamente a bodega: '.trim($reason),
                metadata: ['rescheduled_count' => $ticket->rescheduled_count],
            );

            return $ticket->refresh();
        });
    }

    public static function allowedStatuses(): array
    {
        return [TicketStatus::Created, TicketStatus::SentToWarehouse, TicketStatus::AssignedToWarehouse, TicketStatus::Picking, TicketStatus::Loading, TicketStatus::Loaded, TicketStatus::Dispatched];
    }

    private function releaseResources(Ticket $ticket): void
    {
        $ticket->assignments()->where('status', TicketAssignmentStatus::Active)->update(['status' => TicketAssignmentStatus::Cancelled]);
        $ticket->forceFill(['current_driver_id' => null, 'current_vehicle_id' => null, 'assigned_by' => null, 'assigned_at' => null])->save();
    }
}

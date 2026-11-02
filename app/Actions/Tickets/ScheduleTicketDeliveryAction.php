<?php

namespace App\Actions\Tickets;

use App\Enums\TicketAssignmentStatus;
use App\Enums\TicketEventType;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketEventService;
use DomainException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ScheduleTicketDeliveryAction
{
    public function __construct(private readonly TicketEventService $events) {}

    public function execute(Ticket $ticket, string $date, string $reason, User $user): Ticket
    {
        $scheduledDate = Carbon::parse($date)->startOfDay();

        if ($scheduledDate->isBefore(today())) {
            throw new DomainException('La fecha de entrega no puede ser anterior al dia actual.');
        }

        if (blank(trim($reason))) {
            throw new DomainException('El motivo del cambio de fecha es obligatorio.');
        }

        if (! in_array($ticket->status, self::allowedStatuses(), true)) {
            throw new DomainException('La fecha de entrega ya no se puede cambiar en el estado actual del ticket.');
        }

        return DB::transaction(function () use ($ticket, $scheduledDate, $reason, $user): Ticket {
            $ticket = Ticket::query()->lockForUpdate()->findOrFail($ticket->id);
            $previousDate = $ticket->scheduled_delivery_date?->toDateString();
            $previousStatus = $ticket->status;

            if ($scheduledDate->isAfter(today()) && in_array($ticket->status, [
                TicketStatus::AssignedToWarehouse,
                TicketStatus::Picking,
                TicketStatus::Loading,
                TicketStatus::Loaded,
                TicketStatus::Dispatched,
            ], true)) {
                $ticket->assignments()->where('status', TicketAssignmentStatus::Active)->update([
                    'status' => TicketAssignmentStatus::Cancelled,
                ]);
                $ticket->resetLoadingChecklist();
                $ticket->forceFill([
                    'status' => TicketStatus::SentToWarehouse,
                    'current_driver_id' => null,
                    'current_vehicle_id' => null,
                    'assigned_by' => null,
                    'assigned_at' => null,
                    'dispatched_at' => null,
                ]);
            }

            $ticket->forceFill([
                'scheduled_delivery_date' => $scheduledDate->toDateString(),
                'delivery_schedule_reason' => trim($reason),
                'delivery_date_changed_at' => now(),
                'delivery_date_changed_by' => $user->id,
            ])->save();

            $this->events->record(
                ticket: $ticket,
                eventType: TicketEventType::DeliveryDateChanged,
                user: $user,
                previousStatus: $previousStatus,
                newStatus: $ticket->status,
                description: 'Fecha de entrega cambiada a '.$scheduledDate->format('d/m/Y').': '.trim($reason),
                metadata: [
                    'previous_date' => $previousDate,
                    'scheduled_delivery_date' => $scheduledDate->toDateString(),
                ],
            );

            return $ticket->refresh();
        });
    }

    public static function allowedStatuses(): array
    {
        return [
            TicketStatus::Created,
            TicketStatus::SentToWarehouse,
            TicketStatus::AssignedToWarehouse,
            TicketStatus::Picking,
            TicketStatus::Loading,
            TicketStatus::Loaded,
            TicketStatus::Dispatched,
            TicketStatus::PendingPartialDispatch,
            TicketStatus::PendingReassignment,
        ];
    }
}

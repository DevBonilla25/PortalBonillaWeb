<?php

namespace App\Actions\Deliveries;

use App\Enums\TicketEventType;
use App\Enums\TicketStatus;
use App\Models\DriverProfile;
use App\Models\Ticket;
use App\Services\TicketEventService;
use App\Services\TicketWorkflowService;
use DomainException;
use Illuminate\Support\Carbon;

class ChangeDriverTicketStatusAction
{
    public function __construct(
        private readonly TicketWorkflowService $workflow,
        private readonly TicketEventService $events,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Ticket $ticket, DriverProfile $driver, TicketStatus $nextStatus, array $data = []): Ticket
    {
        abort_unless((int) $ticket->current_driver_id === (int) $driver->id, 404);

        if ($nextStatus === TicketStatus::InRoute) {
            throw new DomainException('Debes usar Iniciar ruta para comenzar todas las entregas.');
        }

        if (in_array($nextStatus, [
            TicketStatus::Dispatched,
            TicketStatus::Delivered,
            TicketStatus::DeliveryFailed,
            TicketStatus::Returning,
            TicketStatus::ArrivedBack,
        ], true)) {
            throw new DomainException('Este estado requiere una acción de bodega o un formulario especializado.');
        }

        $previousStatus = $ticket->status;
        $ticket = $this->workflow->transition($ticket, $nextStatus);
        $occurredAt = isset($data['occurred_at']) ? Carbon::parse($data['occurred_at']) : now();

        $this->events->record(
            ticket: $ticket,
            eventType: TicketEventType::StatusChanged,
            user: $driver->user,
            previousStatus: $previousStatus,
            newStatus: $nextStatus,
            driver: $driver,
            description: $data['description'] ?? 'Cambio de estado desde API de chofer.',
            metadata: $data['metadata'] ?? [],
            latitude: $data['latitude'] ?? null,
            longitude: $data['longitude'] ?? null,
            accuracy: $data['accuracy'] ?? null,
            occurredAt: $occurredAt,
            source: 'mobile',
            connectionStatus: $data['connection_status'] ?? 'online',
            localEventId: $data['local_event_id'] ?? null,
        );

        if (isset($data['latitude'], $data['longitude'])) {
            $driver->forceFill([
                'last_latitude' => $data['latitude'],
                'last_longitude' => $data['longitude'],
                'last_location_at' => $occurredAt,
                'last_connection_at' => now(),
            ])->save();
        }

        return $ticket;
    }
}

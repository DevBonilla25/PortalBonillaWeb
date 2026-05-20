<?php

namespace App\Actions\Deliveries;

use App\Enums\TicketEventType;
use App\Models\DriverProfile;
use App\Models\Ticket;
use App\Models\TicketNovelty;
use App\Services\TicketEventService;
use Illuminate\Support\Carbon;

class RegisterTicketNoveltyAction
{
    public function __construct(
        private readonly TicketEventService $events,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Ticket $ticket, DriverProfile $driver, array $data): TicketNovelty
    {
        abort_unless((int) $ticket->current_driver_id === (int) $driver->id, 404);

        $occurredAt = isset($data['occurred_at']) ? Carbon::parse($data['occurred_at']) : now();

        $novelty = $ticket->novelties()->create([
            'reported_by' => $driver->user_id,
            'driver_id' => $driver->id,
            'novelty_type' => $data['novelty_type'],
            'description' => $data['description'],
            'status' => $data['status'] ?? 'open',
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'accuracy' => $data['accuracy'] ?? null,
            'occurred_at' => $occurredAt,
        ]);

        $this->events->record(
            ticket: $ticket,
            eventType: TicketEventType::NoveltyReported,
            user: $driver->user,
            driver: $driver,
            description: $data['description'],
            metadata: [
                'novelty_id' => $novelty->id,
                'novelty_type' => $novelty->novelty_type,
            ],
            latitude: $data['latitude'] ?? null,
            longitude: $data['longitude'] ?? null,
            accuracy: $data['accuracy'] ?? null,
            occurredAt: $occurredAt,
            source: 'mobile',
            connectionStatus: $data['connection_status'] ?? 'online',
            localEventId: $data['local_event_id'] ?? null,
        );

        return $novelty->refresh();
    }
}

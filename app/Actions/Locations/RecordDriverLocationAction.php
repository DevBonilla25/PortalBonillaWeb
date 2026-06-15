<?php

namespace App\Actions\Locations;

use App\Enums\TicketEventType;
use App\Models\DriverProfile;
use App\Models\LocationPoint;
use App\Models\Ticket;
use App\Services\TicketEventService;
use Illuminate\Support\Carbon;

class RecordDriverLocationAction
{
    public function __construct(
        private readonly TicketEventService $events,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(DriverProfile $driver, array $data): LocationPoint
    {
        $ticket = isset($data['ticket_id'])
            ? Ticket::query()->findOrFail($data['ticket_id'])
            : null;

        if ($ticket !== null) {
            abort_unless((int) $ticket->current_driver_id === (int) $driver->id, 404);
        }

        $recordedAt = isset($data['recorded_at']) ? Carbon::parse($data['recorded_at']) : now();

        $attributes = [
            'ticket_id' => $ticket?->id,
            'vehicle_id' => $ticket?->current_vehicle_id ?? $driver->default_vehicle_id,
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'accuracy' => $data['accuracy'] ?? null,
            'speed' => $data['speed'] ?? null,
            'heading' => $data['heading'] ?? null,
            'battery_level' => $data['battery_level'] ?? null,
            'recorded_at' => $recordedAt,
            'received_at' => now(),
            'source' => $data['source'] ?? 'mobile',
            'metadata' => $data['metadata'] ?? null,
        ];

        $point = isset($data['local_event_id'])
            ? LocationPoint::query()->firstOrCreate(
                [
                    'driver_id' => $driver->id,
                    'local_event_id' => $data['local_event_id'],
                ],
                $attributes,
            )
            : LocationPoint::query()->create([
                'driver_id' => $driver->id,
                'local_event_id' => null,
                'ticket_id' => $ticket?->id,
                'vehicle_id' => $ticket?->current_vehicle_id ?? $driver->default_vehicle_id,
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'accuracy' => $data['accuracy'] ?? null,
                'speed' => $data['speed'] ?? null,
                'heading' => $data['heading'] ?? null,
                'battery_level' => $data['battery_level'] ?? null,
                'recorded_at' => $recordedAt,
                'received_at' => now(),
                'source' => $data['source'] ?? 'mobile',
                'metadata' => $data['metadata'] ?? null,
            ]);

        $driver->forceFill([
            'last_latitude' => $data['latitude'],
            'last_longitude' => $data['longitude'],
            'last_location_at' => $recordedAt,
            'last_connection_at' => now(),
        ])->save();

        if ($ticket !== null && $point->wasRecentlyCreated) {
            $this->events->record(
                ticket: $ticket,
                eventType: TicketEventType::LocationRecorded,
                user: $driver->user,
                driver: $driver,
                description: 'Ubicacion registrada desde API.',
                metadata: ['location_point_id' => $point->id],
                latitude: $data['latitude'],
                longitude: $data['longitude'],
                accuracy: $data['accuracy'] ?? null,
                occurredAt: $recordedAt,
                source: 'mobile',
                connectionStatus: $data['connection_status'] ?? 'online',
                localEventId: $data['local_event_id'] ?? null,
            );
        }

        return $point->refresh();
    }
}

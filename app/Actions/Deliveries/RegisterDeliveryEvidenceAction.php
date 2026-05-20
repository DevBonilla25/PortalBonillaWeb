<?php

namespace App\Actions\Deliveries;

use App\Enums\TicketEventType;
use App\Models\DeliveryEvidence;
use App\Models\DriverProfile;
use App\Models\Ticket;
use App\Services\TicketEventService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;

class RegisterDeliveryEvidenceAction
{
    public function __construct(
        private readonly TicketEventService $events,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Ticket $ticket, DriverProfile $driver, array $data, ?UploadedFile $photo = null, ?UploadedFile $signature = null): DeliveryEvidence
    {
        abort_unless((int) $ticket->current_driver_id === (int) $driver->id, 404);

        $photoPath = $photo?->store("tickets/{$ticket->id}/evidences", 'public');
        $signaturePath = $signature?->store("tickets/{$ticket->id}/signatures", 'public');
        $occurredAt = isset($data['occurred_at']) ? Carbon::parse($data['occurred_at']) : now();

        $evidence = $ticket->deliveryEvidences()->create([
            'driver_id' => $driver->id,
            'received_by_name' => $data['received_by_name'],
            'received_by_identification' => $data['received_by_identification'] ?? null,
            'photo_path' => $photoPath,
            'signature_path' => $signaturePath,
            'observation' => $data['observation'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'accuracy' => $data['accuracy'] ?? null,
            'occurred_at' => $occurredAt,
        ]);

        $this->events->record(
            ticket: $ticket,
            eventType: TicketEventType::DeliveryEvidenceRegistered,
            user: $driver->user,
            driver: $driver,
            description: 'Evidencia de entrega registrada desde API.',
            metadata: ['evidence_id' => $evidence->id],
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

        return $evidence->refresh();
    }
}

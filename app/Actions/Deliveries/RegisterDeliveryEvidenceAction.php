<?php

namespace App\Actions\Deliveries;

use App\Enums\TicketEventType;
use App\Enums\TicketStatus;
use App\Models\DeliveryEvidence;
use App\Models\DriverProfile;
use App\Models\Ticket;
use App\Services\TicketEventService;
use App\Services\TicketWorkflowService;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;

class RegisterDeliveryEvidenceAction
{
    public function __construct(
        private readonly TicketEventService $events,
        private readonly TicketWorkflowService $workflow,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, UploadedFile>  $photos
     */
    public function execute(Ticket $ticket, DriverProfile $driver, array $data, ?UploadedFile $photo = null, array $photos = [], ?UploadedFile $signature = null): DeliveryEvidence
    {
        abort_unless((int) $ticket->current_driver_id === (int) $driver->id, 404);

        $previousStatus = $ticket->status instanceof TicketStatus
            ? $ticket->status
            : TicketStatus::from($ticket->status);

        if ($previousStatus !== TicketStatus::Delivered && ! $previousStatus->canTransitionTo(TicketStatus::Delivered)) {
            throw new DomainException('Solo puedes registrar evidencia cuando el ticket este listo para marcarse como entregado.');
        }

        $mediaDisk = config('filesystems.logistics_media_disk', 'public');
        $imageFiles = $this->imageFiles($photo, $photos);

        $storedImages = $this->storeImages(
            files: $imageFiles,
            ticket: $ticket,
            driver: $driver,
            disk: $mediaDisk,
            directory: "tickets/{$ticket->ticket_code}/evidences",
        );

        $photoPath = $storedImages[0]['path'] ?? null;
        $signaturePath = $signature?->store("tickets/{$ticket->ticket_code}/signatures", $mediaDisk);
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

        foreach ($storedImages as $image) {
            $evidence->mediaAttachments()->create($image);
        }

        $this->events->record(
            ticket: $ticket,
            eventType: TicketEventType::DeliveryEvidenceRegistered,
            user: $driver->user,
            driver: $driver,
            description: 'Evidencia de entrega registrada desde APP.',
            metadata: ['evidence_id' => $evidence->id],
            latitude: $data['latitude'] ?? null,
            longitude: $data['longitude'] ?? null,
            accuracy: $data['accuracy'] ?? null,
            occurredAt: $occurredAt,
            source: 'mobile',
            connectionStatus: $data['connection_status'] ?? 'online',
            localEventId: $data['local_event_id'] ?? null,
        );

        if ($previousStatus !== TicketStatus::Delivered) {
            $ticket = $this->workflow->transition($ticket, TicketStatus::Delivered);

            $this->events->record(
                ticket: $ticket,
                eventType: TicketEventType::StatusChanged,
                user: $driver->user,
                previousStatus: $previousStatus,
                newStatus: TicketStatus::Delivered,
                driver: $driver,
                description: 'Ticket marcado como entregado al guardar evidencia de entrega.',
                metadata: ['evidence_id' => $evidence->id],
                latitude: $data['latitude'] ?? null,
                longitude: $data['longitude'] ?? null,
                accuracy: $data['accuracy'] ?? null,
                occurredAt: $occurredAt,
                source: 'mobile',
                connectionStatus: $data['connection_status'] ?? 'online',
                localEventId: $data['local_event_id'] ?? null,
            );
        }

        if (isset($data['latitude'], $data['longitude'])) {
            $driver->forceFill([
                'last_latitude' => $data['latitude'],
                'last_longitude' => $data['longitude'],
                'last_location_at' => $occurredAt,
                'last_connection_at' => now(),
            ])->save();
        }

        return $evidence->refresh()->load(['ticket', 'mediaAttachments']);
    }

    /**
     * @param  array<int, UploadedFile>  $photos
     * @return array<int, UploadedFile>
     */
    private function imageFiles(?UploadedFile $photo, array $photos): array
    {
        return collect([$photo, ...$photos])
            ->filter(fn ($file): bool => $file instanceof UploadedFile)
            ->values()
            ->all();
    }

    /**
     * @param  array<int, UploadedFile>  $files
     * @return array<int, array<string, mixed>>
     */
    private function storeImages(array $files, Ticket $ticket, DriverProfile $driver, string $disk, string $directory): array
    {
        return collect($files)
            ->map(function (UploadedFile $file, int $index) use ($ticket, $driver, $disk, $directory): array {
                return [
                    'ticket_id' => $ticket->id,
                    'driver_id' => $driver->id,
                    'collection' => 'delivery_evidence_photos',
                    'disk' => $disk,
                    'path' => $file->store($directory, $disk),
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'sort_order' => $index,
                ];
            })
            ->all();
    }
}

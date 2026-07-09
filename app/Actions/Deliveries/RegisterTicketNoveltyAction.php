<?php

namespace App\Actions\Deliveries;

use App\Enums\TicketEventType;
use App\Models\DriverProfile;
use App\Models\NoveltyReason;
use App\Models\Ticket;
use App\Models\TicketNovelty;
use App\Services\TicketEventService;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;

class RegisterTicketNoveltyAction
{
    public function __construct(
        private readonly TicketEventService $events,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, UploadedFile>  $photos
     */
    public function execute(Ticket $ticket, DriverProfile $driver, array $data, ?UploadedFile $photo = null, array $photos = []): TicketNovelty
    {
        abort_unless((int) $ticket->current_driver_id === (int) $driver->id, 404);

        $occurredAt = isset($data['occurred_at']) ? Carbon::parse($data['occurred_at']) : now();
        $reason = $this->resolveReason($driver, $data['novelty_reason_id'] ?? null);
        $imageFiles = $this->imageFiles($photo, $photos);

        if ($reason?->requires_photo && $imageFiles === []) {
            throw new DomainException('Este motivo de novedad requiere una foto.');
        }

        $mediaDisk = config('filesystems.logistics_media_disk', 'public');
        $storedImages = $this->storeImages(
            files: $imageFiles,
            ticket: $ticket,
            driver: $driver,
            disk: $mediaDisk,
            directory: "tickets/{$ticket->ticket_code}/novelties",
        );
        $photoPath = $storedImages[0]['path'] ?? null;

        $novelty = $ticket->novelties()->create([
            'reported_by' => $driver->user_id,
            'driver_id' => $driver->id,
            'novelty_reason_id' => $reason?->id,
            'novelty_type' => $reason?->code ?? $data['novelty_type'],
            'description' => $data['description'],
            'photo_path' => $photoPath,
            'status' => $data['status'] ?? 'open',
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'accuracy' => $data['accuracy'] ?? null,
            'occurred_at' => $occurredAt,
        ]);

        foreach ($storedImages as $image) {
            $novelty->mediaAttachments()->create($image);
        }

        $this->events->record(
            ticket: $ticket,
            eventType: TicketEventType::NoveltyReported,
            user: $driver->user,
            driver: $driver,
            description: $data['description'],
            metadata: [
                'novelty_id' => $novelty->id,
                'novelty_type' => $novelty->novelty_type,
                'novelty_reason_id' => $novelty->novelty_reason_id,
            ],
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

        return $novelty->refresh()->load(['reason', 'mediaAttachments']);
    }

    private function resolveReason(DriverProfile $driver, mixed $reasonId): ?NoveltyReason
    {
        if (! $reasonId) {
            return null;
        }

        $reason = NoveltyReason::query()
            ->whereKey($reasonId)
            ->where('company_id', $driver->user?->company_id)
            ->active()
            ->first();

        if (! $reason) {
            throw new DomainException('El motivo de novedad no esta disponible.');
        }

        return $reason;
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
                    'collection' => 'ticket_novelty_photos',
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

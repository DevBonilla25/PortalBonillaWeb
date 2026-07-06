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
     */
    public function execute(Ticket $ticket, DriverProfile $driver, array $data, ?UploadedFile $photo = null): TicketNovelty
    {
        abort_unless((int) $ticket->current_driver_id === (int) $driver->id, 404);

        $occurredAt = isset($data['occurred_at']) ? Carbon::parse($data['occurred_at']) : now();
        $reason = $this->resolveReason($driver, $data['novelty_reason_id'] ?? null);

        if ($reason?->requires_photo && ! $photo) {
            throw new DomainException('Este motivo de novedad requiere una foto.');
        }

        $photoPath = $photo?->store("tickets/{$ticket->id}/novelties", 'public');

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

        return $novelty->refresh()->load('reason');
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
}

<?php

namespace App\Actions\Deliveries;

use App\Enums\TicketEventType;
use App\Enums\TicketStatus;
use App\Models\DriverProfile;
use App\Models\Ticket;
use App\Models\TicketNovelty;
use App\Services\TicketEventService;
use App\Services\TicketWorkflowService;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RegisterFailedDeliveryAction
{
    public function __construct(
        private readonly RegisterTicketNoveltyAction $novelties,
        private readonly TicketWorkflowService $workflow,
        private readonly TicketEventService $events,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, UploadedFile>  $photos
     */
    public function execute(Ticket $ticket, DriverProfile $driver, array $data, ?UploadedFile $photo = null, array $photos = []): TicketNovelty
    {
        abort_unless((int) $ticket->current_driver_id === (int) $driver->id, 404);

        if (! in_array($ticket->status, [TicketStatus::AtDestination, TicketStatus::Unloading], true)) {
            throw new DomainException('Solo puedes cerrar una entrega fallida después de registrar la llegada al destino.');
        }

        $this->assertReturnItemsBelongToTicket($ticket, $data['return_items'] ?? []);

        return DB::transaction(function () use ($ticket, $driver, $data, $photo, $photos): TicketNovelty {
            $occurredAt = isset($data['occurred_at']) ? Carbon::parse($data['occurred_at']) : now();
            $previousStatus = $ticket->status;
            $novelty = $this->novelties->execute(
                ticket: $ticket,
                driver: $driver,
                data: [...$data, 'status' => 'closed'],
                photo: $photo,
                photos: $photos,
            );

            $ticket = $this->workflow->transition($ticket, TicketStatus::Returning);
            $attempt = $ticket->deliveryAttempts()->where('status', 'in_progress')->latest('id')->first();

            $attempt?->forceFill([
                'status' => 'failed_returning',
                'failure_novelty_id' => $novelty->id,
                'return_items' => $data['return_items'] ?? null,
                'goods_remain_on_vehicle' => true,
                'completed_at' => $occurredAt,
            ])->save();

            $this->events->record(
                ticket: $ticket,
                eventType: TicketEventType::DeliveryFailed,
                user: $driver->user,
                previousStatus: $previousStatus,
                newStatus: TicketStatus::Returning,
                driver: $driver,
                description: 'Entrega no realizada; ticket enviado a retorno.',
                metadata: [
                    'novelty_id' => $novelty->id,
                    'delivery_attempt_id' => $attempt?->id,
                    'return_items' => $data['return_items'] ?? [],
                ],
                latitude: $data['latitude'] ?? null,
                longitude: $data['longitude'] ?? null,
                accuracy: $data['accuracy'] ?? null,
                occurredAt: $occurredAt,
                source: 'mobile',
                connectionStatus: $data['connection_status'] ?? 'online',
                localEventId: $data['local_event_id'] ?? null,
            );

            return $novelty->refresh()->load(['reason', 'mediaAttachments', 'ticket']);
        });
    }

    /** @param array<int, array<string, mixed>> $returnItems */
    private function assertReturnItemsBelongToTicket(Ticket $ticket, array $returnItems): void
    {
        $ids = collect($returnItems)->pluck('ticket_item_id')->map(fn ($id): int => (int) $id);

        if ($ids->isNotEmpty() && $ticket->items()->whereKey($ids)->count() !== $ids->count()) {
            throw new DomainException('Uno o más productos de retorno no pertenecen al ticket.');
        }
    }
}

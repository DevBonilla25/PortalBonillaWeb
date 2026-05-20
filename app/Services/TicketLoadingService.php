<?php

namespace App\Services;

use App\Actions\Tickets\ChangeTicketStatusAction;
use App\Enums\TicketEventType;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use App\Support\Tickets\LoadingChecklistValidation;
use DomainException;

class TicketLoadingService
{
    public function __construct(
        private readonly TicketEventService $events,
        private readonly ChangeTicketStatusAction $changeStatus,
    ) {}

    /**
     * @param  list<array{id:int, loaded_quantity:mixed, is_loaded:mixed, load_observation?:string|null}>  $items
     */
    public function reviewChecklist(Ticket $ticket, array $items, User $reviewedBy): Ticket
    {
        if (! in_array($ticket->status, [TicketStatus::Loading, TicketStatus::Loaded], true)) {
            throw new DomainException('El checklist solo aplica a tickets en carga.');
        }

        if (! $ticket->items()->exists()) {
            throw new DomainException('El ticket no tiene productos para revisar.');
        }

        LoadingChecklistValidation::validateOrFailDomain($items);

        foreach ($items as $itemData) {
            $item = $ticket->items()->findOrFail($itemData['id']);
            $loadedQuantity = $itemData['loaded_quantity'] ?? $item->quantity;
            $observation = trim((string) ($itemData['load_observation'] ?? ''));

            $item->forceFill([
                'loaded_quantity' => $loadedQuantity,
                'is_loaded' => (bool) ($itemData['is_loaded'] ?? false),
                'load_observation' => $observation !== '' ? $observation : null,
                'load_reviewed_by' => $reviewedBy->id,
                'load_reviewed_at' => now(),
            ])->save();
        }

        $this->events->record(
            ticket: $ticket,
            eventType: TicketEventType::LoadingChecklistReviewed,
            user: $reviewedBy,
            previousStatus: $ticket->status,
            newStatus: $ticket->status,
            description: 'Checklist de carga revisado.',
        );

        $ticket = $ticket->refresh();

        if (in_array($ticket->status, [TicketStatus::Loading, TicketStatus::Loaded], true)
            && $ticket->status->canTransitionTo(TicketStatus::Dispatched)) {
            return $this->changeStatus->execute(
                ticket: $ticket,
                nextStatus: TicketStatus::Dispatched,
                user: $reviewedBy,
                description: 'Despacho tras revision de checklist de carga.',
            );
        }

        return $ticket;
    }
}

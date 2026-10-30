<?php

namespace App\Actions\Deliveries;

use App\Enums\TicketEventType;
use App\Enums\TicketStatus;
use App\Models\DeliveryEvidence;
use App\Models\DeliveryEvidenceItem;
use App\Models\Ticket;
use App\Services\TicketEventService;
use DomainException;

class CreatePartialTicketAction
{
    public function __construct(private readonly TicketEventService $events) {}

    public function execute(Ticket $ticket, DeliveryEvidence $evidence, array $items): ?Ticket
    {
        $quantities = $this->validatedQuantities($ticket, $items, 'delivered_quantity');
        foreach ($ticket->items as $item) {
            DeliveryEvidenceItem::query()->updateOrCreate(
                ['delivery_evidence_id' => $evidence->id, 'ticket_item_id' => $item->id],
                ['delivered_quantity' => $quantities[$item->id]],
            );
        }

        return $this->createRemainder($ticket, $quantities, $evidence->id);
    }

    public function executeFromLoading(Ticket $ticket, array $items): ?Ticket
    {
        return $this->createRemainder($ticket, $this->validatedQuantities($ticket, $items, 'loaded_quantity'));
    }

    private function validatedQuantities(Ticket $ticket, array $items, string $quantityKey): array
    {
        $ticket->loadMissing('items');
        $reported = collect($items)->keyBy(fn (array $item): int => (int) ($item['ticket_item_id'] ?? $item['id'] ?? 0));
        $expectedIds = $ticket->items->pluck('id')->sort()->values()->all();
        $receivedIds = $reported->keys()->map(fn ($id): int => (int) $id)->sort()->values()->all();
        if ($receivedIds !== $expectedIds) {
            throw new DomainException('Debes informar la cantidad de todos los productos del ticket.');
        }
        $quantities = [];
        foreach ($ticket->items as $item) {
            $requested = (float) $item->quantity;
            $maximum = $quantityKey === 'delivered_quantity' ? (float) ($item->loaded_quantity ?? $requested) : $requested;
            $quantity = (float) ($reported->get($item->id)[$quantityKey] ?? 0);
            if ($quantity < 0 || $quantity > $maximum) {
                throw new DomainException("Cantidad invalida para {$item->product_name}.");
            }
            $quantities[$item->id] = $quantity;
        }

        return $quantities;
    }

    private function createRemainder(Ticket $ticket, array $quantities, ?int $evidenceId = null): ?Ticket
    {
        $remaining = [];
        foreach ($ticket->items as $item) {
            $quantity = (float) $item->quantity - $quantities[$item->id];
            if ($quantity > 0.00001) {
                $remaining[] = [$item, $quantity];
            }
        }
        if ($remaining === []) {
            return null;
        }
        $existing = Ticket::query()->where('source_ticket_id', $ticket->id)->first();
        if ($existing) {
            return $existing;
        }
        $rootId = $ticket->parent_ticket_id ?: $ticket->id;
        $root = Ticket::query()->findOrFail($rootId);
        $sequence = ((int) Ticket::query()->where('parent_ticket_id', $rootId)->max('partial_sequence')) + 1;
        $child = new Ticket;
        $child->forceFill([
            ...$ticket->only(['company_id', 'branch_id', 'warehouse_id', 'contact_id', 'zone_id', 'subzone_id', 'cashier_id', 'delivery_type', 'guide_number', 'external_source', 'external_source_type', 'external_invoice_id', 'external_document_number', 'external_warehouse_id', 'external_cashier_id', 'external_snapshot', 'customer_name', 'customer_phone', 'customer_phone_2', 'delivery_address', 'delivery_reference', 'google_maps_url', 'priority', 'observations']),
            'external_invoice_id' => null,
            'parent_ticket_id' => $rootId,
            'source_ticket_id' => $ticket->id,
            'partial_sequence' => $sequence,
            'ticket_code' => $root->ticket_code.'-'.str_pad((string) $sequence, 2, '0', STR_PAD_LEFT),
            'status' => TicketStatus::PendingPartialDispatch,
        ]);
        $child->save();
        foreach ($remaining as [$item, $quantity]) {
            $child->items()->create([...$item->only(['product_code', 'external_line', 'external_item_id', 'external_unit_id', 'external_snapshot', 'product_name', 'unit', 'observations']), 'quantity' => $quantity]);
        }
        $ticket->forceFill(['fulfillment_status' => 'partial'])->save();
        $this->events->record(ticket: $child, eventType: TicketEventType::Created, description: "Faltante generado desde {$ticket->ticket_code}.", metadata: array_filter(['parent_ticket_id' => $rootId, 'source_ticket_id' => $ticket->id, 'delivery_evidence_id' => $evidenceId], fn ($value): bool => $value !== null));

        return $child;
    }
}

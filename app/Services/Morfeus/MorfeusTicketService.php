<?php

namespace App\Services\Morfeus;

use App\Models\User;
use App\Models\WarehouseExternalMapping;
use App\Repositories\Morfeus\MorfeusTicketRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class MorfeusTicketService
{
    /**
     * @var array<int, array<string, mixed>|null>
     */
    private array $warehouseMappingCache = [];

    public function __construct(
        private readonly MorfeusTicketRepository $tickets,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, mixed>>
     */
    public function dispatchTicketsForCashier(User $user, array $filters = []): Collection
    {
        if (! $user->morfeus_user_id) {
            return collect();
        }

        return $this->tickets
            ->dispatchTicketsForCashier((int) $user->morfeus_user_id, $filters)
            ->map(fn (object $ticket): array => ($filters['mode'] ?? 'pending_warehouse') === 'pending_warehouse'
                ? $this->normalizePendingInvoiceTicket($ticket)
                : $this->normalizeDispatchTicket($ticket));
    }

    /**
     * @return array<string, mixed>|null
     */
    public function dispatchTicketDetailForCashier(User $user, int $externalDispatchId, bool $pendingOnly = false): ?array
    {
        if (! $user->morfeus_user_id) {
            return null;
        }

        $header = $this->tickets->dispatchTicketForCashier(
            morfeusUserId: (int) $user->morfeus_user_id,
            externalDispatchId: $externalDispatchId,
        );

        if (! $header) {
            return null;
        }

        $delivery = $this->tickets->relatedDelivery($externalDispatchId);

        return array_merge($this->normalizeDispatchTicket($header), [
            'source_document' => [
                'external_id' => $header->documento_origen_id,
                'type' => 'unknown',
                'number' => null,
            ],
            'delivery' => [
                'external_delivery_id' => $delivery?->entrega_id,
                'ticket_number' => $delivery?->numero_ticket_entrega,
                'delivered_at' => $this->formatDate($delivery?->fecha_entrega),
                'morfeus_status' => $delivery?->estado_entrega,
                'delivered_by' => $delivery?->entregado_por,
            ],
            'items' => $this->tickets
                ->dispatchTicketItems($externalDispatchId, $pendingOnly)
                ->map(fn (object $item): array => $this->normalizeDispatchItem($item))
                ->values()
                ->all(),
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function pendingInvoiceDetailForCashier(User $user, int $invoiceId, int $warehouseId): ?array
    {
        if (! $user->morfeus_user_id) {
            return null;
        }

        $header = $this->tickets->pendingInvoiceForCashier(
            morfeusUserId: (int) $user->morfeus_user_id,
            invoiceId: $invoiceId,
            warehouseId: $warehouseId,
        );

        if (! $header) {
            return null;
        }

        return array_merge($this->normalizePendingInvoiceTicket($header), [
            'source_document' => [
                'external_id' => $header->factura_id,
                'type' => 'invoice',
                'number' => $header->numero_factura ?? $header->secuencia_factura,
            ],
            'delivery' => [
                'external_delivery_id' => null,
                'ticket_number' => null,
                'delivered_at' => null,
                'morfeus_status' => null,
                'delivered_by' => null,
            ],
            'items' => $this->tickets
                ->pendingInvoiceItems($invoiceId, $warehouseId)
                ->map(fn (object $item): array => $this->normalizePendingInvoiceItem($item))
                ->values()
                ->all(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeDispatchTicket(object $ticket): array
    {
        return [
            'source_type' => 'dispatch',
            'detail_id' => $ticket->despacho_id,
            'external_dispatch_id' => $ticket->despacho_id,
            'ticket_number' => $ticket->numero_ticket,
            'issued_at' => $this->formatDate($ticket->fecha_despacho),
            'warehouse' => [
                'external_id' => $ticket->bodega_id,
                'name' => $ticket->bodega,
                'mapped_warehouse' => $this->mappedWarehouse($ticket->bodega_id),
            ],
            'source_document_id' => $ticket->documento_origen_id,
            'sequence' => $ticket->secuencia,
            'cashier' => [
                'external_id' => $ticket->usuario_id,
                'name' => $ticket->usuario,
            ],
            'morfeus_status' => $ticket->estado_morfeus,
            'logistic_status' => $this->logisticStatus($ticket->estado_morfeus),
            'items_count' => $ticket->items_count ?? null,
            'pending_items_count' => $ticket->pending_items_count ?? null,
            'pending_quantity' => $ticket->pending_quantity ?? null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizePendingInvoiceTicket(object $ticket): array
    {
        return [
            'source_type' => 'pending_invoice',
            'detail_id' => $ticket->factura_id,
            'external_dispatch_id' => null,
            'ticket_number' => $ticket->secuencia_factura ?? $ticket->numero_factura,
            'issued_at' => $this->formatDate($ticket->fecha_factura),
            'warehouse' => [
                'external_id' => $ticket->bodega_id,
                'name' => $ticket->bodega,
                'mapped_warehouse' => $this->mappedWarehouse($ticket->bodega_id),
            ],
            'source_document_id' => $ticket->factura_id,
            'sequence' => $ticket->secuencia_factura,
            'cashier' => [
                'external_id' => $ticket->usuario_id,
                'name' => $ticket->usuario,
            ],
            'morfeus_status' => $ticket->estado_morfeus,
            'logistic_status' => 'pending',
            'items_count' => $ticket->items_count ?? null,
            'pending_items_count' => $ticket->pending_items_count ?? null,
            'pending_quantity' => $ticket->pending_quantity ?? null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeDispatchItem(object $item): array
    {
        return [
            'line' => $item->linea,
            'external_item_id' => $item->item_id,
            'barcode' => $item->codigo_barra,
            'alternative_code' => $item->codigo_alterno,
            'description' => $item->producto,
            'unit' => [
                'external_id' => $item->unidad_id,
                'name' => $item->unidad,
            ],
            'quantity_to_dispatch' => $item->cantidad_a_despachar,
            'quantity_dispatched' => $item->cantidad_despachada,
            'pending_quantity' => $item->cantidad_pendiente,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizePendingInvoiceItem(object $item): array
    {
        return [
            'line' => $item->linea,
            'external_item_id' => $item->item_id,
            'barcode' => $item->codigo_barra,
            'alternative_code' => $item->codigo_alterno,
            'description' => $item->producto,
            'unit' => [
                'external_id' => $item->unidad_id,
                'name' => $item->unidad,
            ],
            'quantity_to_dispatch' => $item->cantidad_facturada,
            'quantity_dispatched' => $item->cantidad_entregada_factura,
            'pending_quantity' => $item->cantidad_pendiente,
            'invoice_dispatched_quantity' => $item->cantidad_despachada_factura,
        ];
    }

    private function formatDate(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    private function logisticStatus(?string $morfeusStatus): string
    {
        return match (strtoupper((string) $morfeusStatus)) {
            'A', 'ANULADO', 'CANCELADO' => 'cancelled',
            'E', 'ENTREGADO' => 'delivered',
            default => 'pending',
        };
    }

    /**
     * @return array<string, mixed>|null
     */
    private function mappedWarehouse(mixed $externalWarehouseId): ?array
    {
        if (blank($externalWarehouseId)) {
            return null;
        }

        $externalWarehouseId = (int) $externalWarehouseId;

        if (array_key_exists($externalWarehouseId, $this->warehouseMappingCache)) {
            return $this->warehouseMappingCache[$externalWarehouseId];
        }

        $mapping = WarehouseExternalMapping::query()
            ->with('warehouse')
            ->where('external_system', 'morfeus')
            ->where('external_warehouse_id', $externalWarehouseId)
            ->where('is_active', true)
            ->orderByRaw("CASE WHEN external_type = 'physical' THEN 0 ELSE 1 END")
            ->first();

        return $this->warehouseMappingCache[$externalWarehouseId] = $mapping?->warehouse ? [
            'id' => $mapping->warehouse->id,
            'code' => $mapping->warehouse->code,
            'name' => $mapping->warehouse->name,
            'external_type' => $mapping->external_type,
        ] : null;
    }
}

<?php

namespace App\Services\Morfeus;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use App\Models\WarehouseExternalMapping;
use App\Repositories\Morfeus\MorfeusTicketRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

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
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function dispatchTicketsForCashier(User $user, array $filters = []): LengthAwarePaginator
    {
        if (! $user->morfeus_user_id) {
            return new LengthAwarePaginator([], 0, (int) ($filters['per_page'] ?? 10));
        }

        $tickets = $this->tickets->dispatchTicketsForCashier((int) $user->morfeus_user_id, $filters);

        $tickets->setCollection($tickets->getCollection()
            ->map(fn (object $ticket): array => ($filters['mode'] ?? 'pending_warehouse') === 'pending_warehouse'
                ? $this->normalizePendingInvoiceTicket($ticket, $user)
                : $this->normalizeDispatchTicket($ticket)));

        return $tickets;
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

        return array_merge($this->normalizePendingInvoiceTicket($header, $user), [
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
     * @return array<string, mixed>|null
     */
    public function pendingInvoiceTicketFormDataForCashier(User $user, int $invoiceId, int $warehouseId): ?array
    {
        $detail = $this->pendingInvoiceDetailForCashier($user, $invoiceId, $warehouseId);

        if (! $detail) {
            return null;
        }

        $mappedWarehouse = $detail['warehouse']['mapped_warehouse'] ?? null;
        $existingTicket = Ticket::query()
            ->where('company_id', $user->company_id)
            ->where('external_source', 'morfeus')
            ->where('external_invoice_id', $invoiceId)
            ->where('external_warehouse_id', $warehouseId)
            ->first();

        return [
            'existing_ticket_id' => $existingTicket?->id,
            'form_data' => [
                'company_id' => $user->company_id,
                'branch_id' => $mappedWarehouse['branch_id'] ?? null,
                'warehouse_id' => $mappedWarehouse['id'] ?? null,
                'cashier_id' => $user->id,
                'ticket_code' => $detail['ticket_number'],
                'guide_number' => $detail['ticket_number'],
                'customer_name' => $detail['customer']['name'] ?? $detail['customer']['recipient_name'] ?? 'Cliente Morfeus',
                'customer_phone' => $detail['customer']['phone'] ?? null,
                'customer_phone_2' => null,
                'delivery_address' => $detail['customer']['delivery_address'] ?? 'Pendiente de completar',
                'delivery_reference' => null,
                'priority' => TicketPriority::Normal->value,
                'status' => TicketStatus::Created->value,
                'external_source' => 'morfeus',
                'external_source_type' => 'pending_invoice',
                'external_invoice_id' => $invoiceId,
                'external_document_number' => $detail['ticket_number'],
                'external_warehouse_id' => $warehouseId,
                'external_cashier_id' => $detail['cashier']['external_id'] ?? null,
                'external_snapshot' => $detail,
                'items' => collect($detail['items'] ?? [])
                    ->map(fn (array $item): array => [
                        'product_code' => $item['barcode'] ?? $item['alternative_code'] ?? null,
                        'external_line' => $item['line'] ?? null,
                        'external_item_id' => $item['external_item_id'] ?? null,
                        'external_unit_id' => $item['unit']['external_id'] ?? null,
                        'external_snapshot' => $item,
                        'product_name' => $item['description'] ?? 'Item Morfeus',
                        'quantity' => $item['pending_quantity'] ?? 0,
                        'unit' => $item['unit']['name'] ?? null,
                        'observations' => null,
                    ])
                    ->values()
                    ->all(),
            ],
            'detail' => $detail,
        ];
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
            'customer' => $this->normalizeCustomer($ticket),
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
    private function normalizePendingInvoiceTicket(object $ticket, ?User $user = null): array
    {
        $localTicket = $user
            ? $this->localTicketForPendingInvoice($user, (int) $ticket->factura_id, (int) $ticket->bodega_id)
            : null;

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
            'customer' => $this->normalizeCustomer($ticket),
            'morfeus_status' => $ticket->estado_morfeus,
            'logistic_status' => 'pending',
            'local_ticket' => [
                'id' => $localTicket?->id,
                'status' => $localTicket?->status?->value,
                'exists' => $localTicket !== null,
            ],
            'items_count' => $ticket->items_count ?? null,
            'pending_items_count' => $ticket->pending_items_count ?? null,
            'pending_quantity' => $ticket->pending_quantity ?? null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeCustomer(object $ticket): array
    {
        return [
            'external_id' => $ticket->cliente_id ?? null,
            'name' => $this->firstFilled($ticket->cliente_nombre ?? null, $ticket->cliente_catalogo_nombre ?? null),
            'identification' => $this->firstFilled($ticket->cliente_identificacion ?? null, $ticket->cliente_catalogo_identificacion ?? null),
            'email' => $this->firstFilled($ticket->cliente_correo ?? null, $ticket->cliente_catalogo_correo ?? null),
            'recipient_name' => $this->firstFilled($ticket->destinatario_nombre ?? null),
            'recipient_identification' => $this->firstFilled($ticket->destinatario_identificacion ?? null),
            'phone' => $this->firstFilled($ticket->destinatario_telefono ?? null, $ticket->cliente_catalogo_telefono ?? null),
            'delivery_address' => $this->firstFilled($ticket->cliente_catalogo_direccion ?? null),
            'invoice_observation' => $this->firstFilled($ticket->observacion_factura ?? null),
        ];
    }

    private function localTicketForPendingInvoice(User $user, int $invoiceId, int $warehouseId): ?Ticket
    {
        return Ticket::query()
            ->where('company_id', $user->company_id)
            ->where('external_source', 'morfeus')
            ->where('external_invoice_id', $invoiceId)
            ->where('external_warehouse_id', $warehouseId)
            ->first();
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

    private function firstFilled(mixed ...$values): ?string
    {
        foreach ($values as $value) {
            if (filled($value)) {
                return trim((string) $value);
            }
        }

        return null;
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
            'branch_id' => $mapping->warehouse->branch_id,
            'external_type' => $mapping->external_type,
        ] : null;
    }
}

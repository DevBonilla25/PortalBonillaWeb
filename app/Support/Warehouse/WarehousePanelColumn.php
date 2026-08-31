<?php

namespace App\Support\Warehouse;

use App\Enums\TicketStatus;

readonly class WarehousePanelColumn
{
    /**
     * @param  list<TicketStatus>  $statuses
     */
    public function __construct(
        public string $key,
        public string $label,
        public string $dotColor,
        public array $statuses,
    ) {}

    /**
     * @return list<self>
     */
    public static function all(): array
    {
        return [
            new self(
                key: 'received',
                label: 'Recibidos desde caja',
                dotColor: 'info',
                statuses: [TicketStatus::SentToWarehouse],
            ),
            new self(
                key: 'preparation',
                label: 'En preparación',
                dotColor: 'warning',
                statuses: [TicketStatus::Picking],
            ),
            new self(
                key: 'loading',
                label: 'Cargando',
                dotColor: 'warning',
                statuses: [TicketStatus::Loading],
            ),
            new self(
                key: 'dispatched',
                label: 'Despachados',
                dotColor: 'primary',
                statuses: [TicketStatus::Dispatched],
            ),
            new self(
                key: 'returns',
                label: 'Devoluciones',
                dotColor: 'danger',
                statuses: [
                    TicketStatus::DeliveryFailed,
                    TicketStatus::Returning,
                    TicketStatus::ArrivedBack,
                    TicketStatus::PendingReassignment,
                ],
            ),
        ];
    }

    public function matchesStatus(TicketStatus $status): bool
    {
        return in_array($status, $this->statuses, true);
    }
}

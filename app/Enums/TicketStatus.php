<?php

namespace App\Enums;

enum TicketStatus: string
{
    case Created = 'created';
    case SentToWarehouse = 'sent_to_warehouse';
    case AssignedToWarehouse = 'assigned_to_warehouse';
    case Picking = 'picking';
    case Loading = 'loading';
    case Loaded = 'loaded';
    case Dispatched = 'dispatched';
    case InRoute = 'in_route';
    case AtDestination = 'at_destination';
    case Unloading = 'unloading';
    case Delivered = 'delivered';
    case DeliveryFailed = 'delivery_failed';
    case Returning = 'returning';
    case ArrivedBack = 'arrived_back';
    case PendingReassignment = 'pending_reassignment';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Created => 'Creado',
            self::SentToWarehouse => 'Enviado a bodega',
            self::AssignedToWarehouse => 'En preparación',
            self::Picking => 'En preparación',
            self::Loading => 'Cargando',
            self::Loaded => 'Cargado',
            self::Dispatched => 'Despachado',
            self::InRoute => 'En ruta',
            self::AtDestination => 'Llegó al destino',
            self::Unloading => 'Descargando',
            self::Delivered => 'Entregado',
            self::DeliveryFailed => 'Entrega no realizada',
            self::Returning => 'En retorno',
            self::ArrivedBack => 'Recibido en bodega',
            self::PendingReassignment => 'Pendiente de reasignación',
            self::Cancelled => 'Cancelado',
        };
    }

    /**
     * @return list<self>
     */
    public function allowedNextStatuses(): array
    {
        return match ($this) {
            self::Created => [self::SentToWarehouse],
            self::SentToWarehouse => [self::Picking],
            self::AssignedToWarehouse => [self::Picking, self::Loading],
            self::Picking => [self::Loading, self::DeliveryFailed],
            self::Loading => [self::Loaded, self::DeliveryFailed],
            self::Loaded => [self::Dispatched],
            self::Dispatched => [self::InRoute],
            self::InRoute => [self::AtDestination],
            self::AtDestination => [self::Unloading, self::Returning],
            self::Unloading => [self::Delivered, self::Returning],
            self::DeliveryFailed => [self::Returning],
            self::Delivered => [],
            self::Returning => [self::ArrivedBack],
            self::ArrivedBack => [self::PendingReassignment],
            self::PendingReassignment => [self::Picking, self::Cancelled],
            self::Cancelled => [],
        };
    }

    /**
     * @return list<self>
     */
    public static function warehousePanelStatuses(): array
    {
        return [
            self::SentToWarehouse,
            self::Picking,
            self::Loading,
            self::Dispatched,
        ];
    }

    public function isWarehousePanelStatus(): bool
    {
        return in_array($this, self::warehousePanelStatuses(), true);
    }

    public function canTransitionTo(self $nextStatus): bool
    {
        return in_array($nextStatus, $this->allowedNextStatuses(), true);
    }
}

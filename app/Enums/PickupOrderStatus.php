<?php

namespace App\Enums;

enum PickupOrderStatus: string
{
    case Assigned = 'assigned';
    case EnRoute = 'en_route';
    case AtPickup = 'at_pickup';
    case Loading = 'loading';
    case PickedUp = 'picked_up';
    case TransportingToWarehouse = 'transporting_to_warehouse';
    case ReceivedAtWarehouse = 'received_at_warehouse';
    case Completed = 'completed';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Assigned => 'Asignado',
            self::EnRoute => 'En ruta al retiro',
            self::AtPickup => 'Llegó al punto de retiro',
            self::Loading => 'Cargando retiro',
            self::PickedUp => 'Retiro realizado',
            self::TransportingToWarehouse => 'En traslado a bodega',
            self::ReceivedAtWarehouse => 'Recibido en bodega',
            self::Completed => 'Cerrado',
            self::Failed => 'Retiro no realizado',
            self::Cancelled => 'Cancelado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Completed, self::ReceivedAtWarehouse => 'success',
            self::Failed, self::Cancelled => 'danger',
            self::Loading, self::AtPickup => 'warning',
            self::Assigned => 'gray',
            default => 'info',
        };
    }

    public function isRouteResolved(): bool
    {
        return in_array($this, [self::PickedUp, self::TransportingToWarehouse, self::ReceivedAtWarehouse, self::Completed, self::Failed, self::Cancelled], true);
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Completed, self::Failed, self::Cancelled], true);
    }
}

<?php

namespace App\Enums;

enum DeliveryRouteStatus: string
{
    case Planned = 'planned';
    case InProgress = 'in_progress';
    case ReturningToWarehouse = 'returning_to_warehouse';
    case AtWarehouse = 'at_warehouse';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Planned => 'Planificada',
            self::InProgress => 'En progreso',
            self::ReturningToWarehouse => 'Retornando a bodega',
            self::AtWarehouse => 'En bodega',
            self::Completed => 'Completada',
            self::Cancelled => 'Cancelada',
        };
    }
}

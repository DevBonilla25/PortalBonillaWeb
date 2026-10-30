<?php

namespace App\Enums;

enum DeliveryRouteEventType: string
{
    case Start = 'start';
    case StartReturn = 'start_return';
    case ArriveWarehouse = 'arrive_warehouse';
    case Complete = 'complete';
    case Cancel = 'cancel';

    public function label(): string
    {
        return match ($this) {
            self::Start => 'Inicio de ruta',
            self::StartReturn => 'Inicio de retorno',
            self::ArriveWarehouse => 'Llegada a bodega',
            self::Complete => 'Ruta completada',
            self::Cancel => 'Ruta cancelada',
        };
    }
}

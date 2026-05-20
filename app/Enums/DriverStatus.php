<?php

namespace App\Enums;

enum DriverStatus: string
{
    case Available = 'AVAILABLE';
    case Assigned = 'ASSIGNED';
    case OnRoute = 'ON_ROUTE';
    case Offline = 'OFFLINE';
    case Suspended = 'SUSPENDED';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Disponible',
            self::Assigned => 'Asignado',
            self::OnRoute => 'En ruta',
            self::Offline => 'Sin conexion',
            self::Suspended => 'Suspendido',
        };
    }
}

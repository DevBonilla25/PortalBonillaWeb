<?php

namespace App\Enums;

enum VehicleStatus: string
{
    case Available = 'AVAILABLE';
    case Assigned = 'ASSIGNED';
    case Maintenance = 'MAINTENANCE';
    case Inactive = 'INACTIVE';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Disponible',
            self::Assigned => 'Asignado',
            self::Maintenance => 'Mantenimiento',
            self::Inactive => 'Inactivo',
        };
    }
}

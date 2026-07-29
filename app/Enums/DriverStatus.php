<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum DriverStatus: string implements HasLabel
{
    case Available = 'AVAILABLE';
    case Assigned = 'ASSIGNED';
    case OnRoute = 'ON_ROUTE';
    case Offline = 'OFFLINE';
    case Suspended = 'SUSPENDED';

    public function getLabel(): ?string
    {
        return $this->label();
    }

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

<?php

namespace App\Enums;

enum LogisticOperationStatus: string
{
    case Planned = 'planned';
    case TravelingToPlant = 'traveling_to_plant';
    case ArrivedAtPlant = 'arrived_at_plant';
    case InQueue = 'in_queue';
    case EnteredPlant = 'entered_plant';
    case Loading = 'loading';
    case Loaded = 'loaded';
    case ReturningToOrigin = 'returning_to_origin';
    case ArrivedAtOrigin = 'arrived_at_origin';
    case Unloading = 'unloading';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Planned => 'Planificada', self::TravelingToPlant => 'Viajando a planta',
            self::ArrivedAtPlant => 'Llegó a planta', self::InQueue => 'En cola',
            self::EnteredPlant => 'Ingresó a planta', self::Loading => 'Cargando',
            self::Loaded => 'Cargado', self::ReturningToOrigin => 'Retornando a origen',
            self::ArrivedAtOrigin => 'Llegó a origen', self::Unloading => 'Descargando',
            self::Completed => 'Completada', self::Cancelled => 'Cancelada',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Planned => 'gray',
            self::Completed => 'success', self::Cancelled => 'danger',
            self::Loading, self::Unloading, self::InQueue => 'warning',
            default => 'info',
        };
    }
}

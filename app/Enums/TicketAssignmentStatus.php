<?php

namespace App\Enums;

enum TicketAssignmentStatus: string
{
    case Active = 'ACTIVE';
    case Replaced = 'REPLACED';
    case Cancelled = 'CANCELLED';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Activa',
            self::Replaced => 'Reemplazada',
            self::Cancelled => 'Cancelada',
        };
    }
}

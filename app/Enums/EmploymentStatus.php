<?php

namespace App\Enums;

enum EmploymentStatus: string
{
    case Active = 'ACTIVE';
    case OnLeave = 'ON_LEAVE';
    case Suspended = 'SUSPENDED';
    case Terminated = 'TERMINATED';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Activo',
            self::OnLeave => 'Con licencia',
            self::Suspended => 'Suspendido',
            self::Terminated => 'Terminado',
        };
    }
}

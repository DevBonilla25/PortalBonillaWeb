<?php

namespace App\Enums;

enum WarehouseType: string
{
    case General = 'GENERAL';
    case Branch = 'BRANCH';
    case Temporary = 'TEMPORARY';
    case External = 'EXTERNAL';

    public function label(): string
    {
        return match ($this) {
            self::General => 'Bodega general',
            self::Branch => 'Bodega de sucursal',
            self::Temporary => 'Temporal',
            self::External => 'Externa',
        };
    }
}

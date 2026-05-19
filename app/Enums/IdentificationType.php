<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum IdentificationType: string implements HasLabel
{
    case Cedula = 'CEDULA';
    case Ruc = 'RUC';
    case Passport = 'PASSPORT';
    case FinalConsumer = 'FINAL_CONSUMER';
    case Other = 'OTHER';

    public function getLabel(): ?string
    {
        return $this->label();
    }

    public function label(): string
    {
        return match ($this) {
            self::Cedula => 'Cédula',
            self::Ruc => 'RUC',
            self::Passport => 'Pasaporte',
            self::FinalConsumer => 'Consumidor final',
            self::Other => 'Otro',
        };
    }
}

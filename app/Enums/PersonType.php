<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PersonType: string implements HasLabel
{
    case Natural = 'NATURAL';
    case Jurídica = 'JURÍDICA';

    public function getLabel(): ?string
    {
        return $this->label();
    }

    public function label(): string
    {
        return match ($this) {
            self::Natural => 'Persona natural',
            self::Jurídica => 'Persona jurídica',
        };
    }
}

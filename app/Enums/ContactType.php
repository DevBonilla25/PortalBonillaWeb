<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ContactType: string implements HasLabel
{
    case Person = 'PERSON';
    case Company = 'COMPANY';

    public function getLabel(): ?string
    {
        return $this->label();
    }

    public function label(): string
    {
        return match ($this) {
            self::Person => 'Persona',
            self::Company => 'Empresa',
        };
    }
}

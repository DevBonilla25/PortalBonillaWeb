<?php

namespace App\Filament\Concerns;

trait HasAdministrationNavigation
{
    protected static string|\UnitEnum|null $navigationGroup = 'Administración';
}

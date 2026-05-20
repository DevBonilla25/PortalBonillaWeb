<?php

namespace App\Filament\Resources\DriverProfiles\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class DriverProfileInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('user.name')->label('Usuario'),
                TextEntry::make('employee.display_name')->label('Empleado')->placeholder('-'),
                TextEntry::make('defaultVehicle.plate')->label('Vehiculo')->placeholder('-'),
                TextEntry::make('status')->label('Estado')->badge(),
                IconEntry::make('is_active')->label('Activo')->boolean(),
                TextEntry::make('license_number')->label('Licencia')->placeholder('-'),
                TextEntry::make('license_type')->label('Tipo')->placeholder('-'),
                TextEntry::make('license_expires_at')->label('Vence')->date()->placeholder('-'),
                TextEntry::make('last_latitude')->label('Ultima latitud')->placeholder('-'),
                TextEntry::make('last_longitude')->label('Ultima longitud')->placeholder('-'),
                TextEntry::make('last_connection_at')->label('Ultima conexion')->dateTime()->placeholder('-'),
                TextEntry::make('observations')
                    ->label('Observaciones')
                    ->placeholder('-')
                    ->columnSpanFull(),
            ]);
    }
}

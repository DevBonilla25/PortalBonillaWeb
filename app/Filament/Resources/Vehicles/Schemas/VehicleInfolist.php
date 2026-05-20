<?php

namespace App\Filament\Resources\Vehicles\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class VehicleInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('company.name')->label('Empresa'),
                TextEntry::make('code')->label('Codigo'),
                TextEntry::make('plate')->label('Placa'),
                TextEntry::make('status')->label('Estado')->badge(),
                TextEntry::make('brand')->label('Marca')->placeholder('-'),
                TextEntry::make('model')->label('Modelo')->placeholder('-'),
                TextEntry::make('type')->label('Tipo')->placeholder('-'),
                TextEntry::make('capacity_kg')->label('Capacidad kg')->placeholder('-'),
                TextEntry::make('volume_m3')->label('Volumen m3')->placeholder('-'),
                IconEntry::make('is_active')->label('Activo')->boolean(),
                TextEntry::make('observations')
                    ->label('Observaciones')
                    ->placeholder('-')
                    ->columnSpanFull(),
            ]);
    }
}

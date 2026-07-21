<?php

namespace App\Filament\Resources\Zones\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ZoneInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('company.name')->label('Empresa'),
                TextEntry::make('code')->label('Codigo'),
                TextEntry::make('name')->label('Nombre'),
                TextEntry::make('color')
                    ->label('Color')
                    ->badge()
                    ->placeholder('-'),
                TextEntry::make('description')
                    ->label('Descripcion')
                    ->placeholder('-')
                    ->columnSpanFull(),
                IconEntry::make('is_active')
                    ->label('Activa')
                    ->boolean(),
            ]);
    }
}

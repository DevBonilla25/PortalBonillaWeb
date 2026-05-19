<?php

namespace App\Filament\Resources\Branches\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class BranchInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('company.name')
                    ->label('Empresa'),
                TextEntry::make('code')
                    ->label('Código'),
                TextEntry::make('name')
                    ->label('Nombre'),
                TextEntry::make('city')
                    ->label('Ciudad')
                    ->placeholder('—'),
                TextEntry::make('phone')
                    ->label('Teléfono')
                    ->placeholder('—'),
                TextEntry::make('address')
                    ->label('Dirección')
                    ->placeholder('—')
                    ->columnSpanFull(),
                IconEntry::make('is_main')
                    ->label('Sucursal matriz')
                    ->boolean(),
                IconEntry::make('is_active')
                    ->label('Activa')
                    ->boolean(),
            ]);
    }
}

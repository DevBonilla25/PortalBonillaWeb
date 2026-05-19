<?php

namespace App\Filament\Resources\Warehouses\Schemas;

use App\Enums\WarehouseType;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class WarehouseInfolist
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
                TextEntry::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (WarehouseType $state): string => $state->label()),
                TextEntry::make('branch.name')
                    ->label('Sucursal')
                    ->placeholder('—'),
                IconEntry::make('is_general')
                    ->label('Bodega general')
                    ->boolean(),
                TextEntry::make('address')
                    ->label('Dirección')
                    ->placeholder('—')
                    ->columnSpanFull(),
                IconEntry::make('is_active')
                    ->label('Activa')
                    ->boolean(),
            ]);
    }
}

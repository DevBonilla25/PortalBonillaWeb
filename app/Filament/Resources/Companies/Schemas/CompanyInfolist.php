<?php

namespace App\Filament\Resources\Companies\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class CompanyInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name')
                    ->label('Razón social'),
                TextEntry::make('commercial_name')
                    ->label('Nombre comercial')
                    ->placeholder('—'),
                TextEntry::make('ruc')
                    ->label('RUC'),
                TextEntry::make('email')
                    ->label('Correo electrónico')
                    ->placeholder('—'),
                TextEntry::make('phone')
                    ->label('Teléfono')
                    ->placeholder('—'),
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

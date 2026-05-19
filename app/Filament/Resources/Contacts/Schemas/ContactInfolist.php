<?php

namespace App\Filament\Resources\Contacts\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ContactInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('company.name')
                    ->label('Empresa'),
                TextEntry::make('contact_type')
                    ->label('Tipo de contacto')
                    ->badge(),
                TextEntry::make('person_type')
                    ->label('Tipo de persona')
                    ->badge(),
                TextEntry::make('display_name')
                    ->label('Nombre'),
                TextEntry::make('identification_type')
                    ->label('Tipo de identificación')
                    ->badge()
                    ->placeholder('—'),
                TextEntry::make('identification_number')
                    ->label('Identificación')
                    ->placeholder('—'),
                TextEntry::make('email')
                    ->label('Correo')
                    ->placeholder('—'),
                TextEntry::make('phone')
                    ->label('Teléfono')
                    ->placeholder('—'),
                TextEntry::make('city')
                    ->label('Ciudad')
                    ->placeholder('—'),
                IconEntry::make('is_customer')
                    ->label('Cliente')
                    ->boolean(),
                IconEntry::make('is_supplier')
                    ->label('Proveedor')
                    ->boolean(),
                IconEntry::make('is_active')
                    ->label('Activo')
                    ->boolean(),
            ]);
    }
}

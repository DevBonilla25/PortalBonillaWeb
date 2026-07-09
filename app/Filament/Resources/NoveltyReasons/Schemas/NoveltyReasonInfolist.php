<?php

namespace App\Filament\Resources\NoveltyReasons\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class NoveltyReasonInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('company.name')->label('Empresa'),
                TextEntry::make('code')->label('Codigo'),
                TextEntry::make('name')->label('Nombre'),
                TextEntry::make('sort_order')->label('Orden'),
                IconEntry::make('requires_photo')
                    ->label('Requiere foto')
                    ->boolean(),
                IconEntry::make('is_active')
                    ->label('Activo')
                    ->boolean(),
                TextEntry::make('description')
                    ->label('Descripcion')
                    ->placeholder('-')
                    ->columnSpanFull(),
            ]);
    }
}

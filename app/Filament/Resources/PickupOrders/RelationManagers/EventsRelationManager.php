<?php

namespace App\Filament\Resources\PickupOrders\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EventsRelationManager extends RelationManager
{
    protected static string $relationship = 'events';

    protected static ?string $title = 'Historial y ubicaciones';

    public function form(Schema $schema): Schema
    {
        return $schema;
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('occurred_at')->label('Fecha')->dateTime('d/m/Y H:i:s')->sortable(),
            TextColumn::make('action')->label('Accion')->badge(), TextColumn::make('from_status')->label('Estado anterior'),
            TextColumn::make('to_status')->label('Estado nuevo'), TextColumn::make('performer.name')->label('Registrado por'),
            TextColumn::make('latitude')->label('Latitud')->placeholder('-'), TextColumn::make('longitude')->label('Longitud')->placeholder('-'),
            TextColumn::make('notes')->label('Notas')->placeholder('-')->wrap(),
        ])->defaultSort('occurred_at', 'desc');
    }
}

<?php

namespace App\Filament\Resources\LogisticOperations\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TransitionsRelationManager extends RelationManager
{
    protected static string $relationship = 'transitions';

    protected static ?string $title = 'Historial del recorrido';

    public function form(Schema $schema): Schema
    {
        return $schema;
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('created_at')->label('Fecha')->dateTime('d/m/Y H:i:s')->sortable(),
            TextColumn::make('action')->label('Acción')->badge(),
            TextColumn::make('from_status')->label('Estado anterior'),
            TextColumn::make('to_status')->label('Estado nuevo'),
            TextColumn::make('performer.name')->label('Ejecutado por')->placeholder('-'),
            TextColumn::make('notes')->label('Notas')->placeholder('-')->limit(50),
        ])->defaultSort('created_at', 'desc');
    }
}

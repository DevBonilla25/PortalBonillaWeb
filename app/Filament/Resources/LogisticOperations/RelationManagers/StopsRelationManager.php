<?php

namespace App\Filament\Resources\LogisticOperations\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StopsRelationManager extends RelationManager
{
    protected static string $relationship = 'stops';

    protected static ?string $title = 'Paradas de ruta';

    public function form(Schema $schema): Schema
    {
        return $schema;
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('reason')->label('Motivo')->formatStateUsing(fn (string $state): string => match ($state) {
                'sleep' => 'Descanso / dormir', 'food' => 'Alimentación', 'personal' => 'Actividad personal',
                'mechanical' => 'Revisión mecánica', default => 'Otro',
            })->badge(),
            TextColumn::make('started_at')->label('Inicio')->dateTime('d/m/Y H:i:s')->sortable(),
            TextColumn::make('finished_at')->label('Fin')->dateTime('d/m/Y H:i:s')->placeholder('En curso'),
            TextColumn::make('notes')->label('Detalle')->placeholder('-')->wrap(),
            TextColumn::make('starter.name')->label('Registrada por')->placeholder('-'),
        ])->defaultSort('started_at', 'desc');
    }
}

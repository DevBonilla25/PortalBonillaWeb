<?php

namespace App\Filament\Resources\Tickets\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EventsRelationManager extends RelationManager
{
    protected static string $relationship = 'events';

    protected static ?string $title = 'Historial';

    public function form(Schema $schema): Schema
    {
        return $schema;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('event_type')
            ->columns([
                TextColumn::make('occurred_at')
                    ->label('Fecha')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('event_type')
                    ->label('Evento')
                    ->badge(),
                TextColumn::make('previous_status')
                    ->label('Estado anterior')
                    ->badge()
                    ->placeholder('-'),
                TextColumn::make('new_status')
                    ->label('Estado nuevo')
                    ->badge()
                    ->placeholder('-'),
                TextColumn::make('user.name')
                    ->label('Usuario')
                    ->placeholder('-'),
                TextColumn::make('description')
                    ->label('Detalle')
                    ->limit(60)
                    ->placeholder('-'),
            ])
            ->defaultSort('occurred_at', 'desc');
    }
}

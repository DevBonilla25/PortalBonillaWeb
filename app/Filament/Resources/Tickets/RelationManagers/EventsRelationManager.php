<?php

namespace App\Filament\Resources\Tickets\RelationManagers;

use App\Enums\TicketEventType;
use App\Enums\TicketStatus;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EventsRelationManager extends RelationManager
{
    private const DISPLAY_TIMEZONE = 'America/Guayaquil';

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
                    ->dateTime(timezone: self::DISPLAY_TIMEZONE)
                    ->sortable(),
                TextColumn::make('event_type')
                    ->label('Evento')
                    ->formatStateUsing(fn (TicketEventType $state): string => $state->label())
                    ->badge(),
                TextColumn::make('previous_status')
                    ->label('Estado anterior')
                    ->formatStateUsing(fn (?TicketStatus $state): ?string => $state?->label())
                    ->badge()
                    ->placeholder('-'),
                TextColumn::make('new_status')
                    ->label('Estado nuevo')
                    ->formatStateUsing(fn (?TicketStatus $state): ?string => $state?->label())
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

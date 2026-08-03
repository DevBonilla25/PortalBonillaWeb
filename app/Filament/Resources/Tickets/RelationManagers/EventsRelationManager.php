<?php

namespace App\Filament\Resources\Tickets\RelationManagers;

use App\Enums\TicketEventType;
use App\Enums\TicketStatus;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;

class EventsRelationManager extends RelationManager
{
    private const DISPLAY_TIMEZONE = 'America/Guayaquil';

    protected static string $relationship = 'events';

    protected static ?string $title = 'Historial';

    public function form(Schema $schema): Schema
    {
        return $schema;
    }

    private static function formatOccurredAt(mixed $state, mixed $record): ?string
    {
        if (blank($state)) {
            return null;
        }

        if ($record?->event_type === TicketEventType::MorfeusInvoiceIssued) {
            return Carbon::parse($record->metadata['issued_at'] ?? $state)->format('d/m/Y H:i:s');
        }

        return Carbon::parse($state)->timezone(self::DISPLAY_TIMEZONE)->format('d/m/Y H:i:s');
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('event_type')
            ->columns([
                TextColumn::make('occurred_at')
                    ->label('Fecha')
                    ->formatStateUsing(fn (mixed $state, mixed $record): ?string => self::formatOccurredAt($state, $record))
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

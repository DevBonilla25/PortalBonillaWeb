<?php

namespace App\Filament\Resources\Tickets\RelationManagers;

use App\Enums\TicketStatus;
use App\Filament\Resources\Tickets\TicketResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PartialTicketsRelationManager extends RelationManager
{
    private const DISPLAY_TIMEZONE = 'America/Guayaquil';

    protected static string $relationship = 'partialTickets';

    protected static ?string $title = 'Entregas faltantes';

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('ticket_code')->label('Ticket')->url(fn ($record): string => TicketResource::getUrl('view', ['record' => $record]))->weight('semibold'),
            TextColumn::make('status')->label('Estado')->formatStateUsing(fn (TicketStatus $state): string => $state->label())->badge(),
            TextColumn::make('fulfillment_status')->label('Cumplimiento')->state(fn ($record): string => $record->fulfillmentLabel())->badge(),
            TextColumn::make('items_count')->counts('items')->label('Productos'),
            TextColumn::make('created_at')->label('Creado')->dateTime('d/m/Y H:i', timezone: self::DISPLAY_TIMEZONE),
        ]);
    }
}

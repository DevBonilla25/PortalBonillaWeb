<?php

namespace App\Filament\Resources\DeliveryRoutes\RelationManagers;

use App\Enums\PickupOrderStatus;
use App\Enums\TicketStatus;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TasksRelationManager extends RelationManager
{
    protected static string $relationship = 'tasks';

    protected static ?string $title = 'Secuencia de entregas y retiros';

    public function form(Schema $schema): Schema
    {
        return $schema;
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('sequence')->label('Orden')->sortable(), TextColumn::make('task_type')->label('Tipo')->formatStateUsing(fn ($state) => $state === 'delivery' ? 'Entrega' : 'Retiro')->badge(),
            TextColumn::make('ticket.ticket_code')->label('Ticket')->placeholder('-'), TextColumn::make('ticket.customer_name')->label('Cliente')->placeholder('-'),
            TextColumn::make('pickupOrder.code')->label('Orden de retiro')->placeholder('-'), TextColumn::make('pickupOrder.pickup_name')->label('Punto de retiro')->placeholder('-'),
            TextColumn::make('ticket.status')->label('Estado entrega')->formatStateUsing(fn (?TicketStatus $state): ?string => $state?->label())->badge()->placeholder('-'), TextColumn::make('pickupOrder.status')->label('Estado retiro')->formatStateUsing(fn (?PickupOrderStatus $state): ?string => $state?->label())->badge()->placeholder('-'),
        ])->defaultSort('sequence');
    }
}

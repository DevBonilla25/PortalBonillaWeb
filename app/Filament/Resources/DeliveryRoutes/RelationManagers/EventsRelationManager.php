<?php

namespace App\Filament\Resources\DeliveryRoutes\RelationManagers;

use App\Enums\DeliveryRouteEventType;
use App\Enums\DeliveryRouteStatus;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EventsRelationManager extends RelationManager
{
    private const DISPLAY_TIMEZONE = 'America/Guayaquil';

    protected static string $relationship = 'events';

    protected static ?string $title = 'Eventos y tiempos';

    public function form(Schema $schema): Schema
    {
        return $schema;
    }

    public function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('recorded_at')->label('Fecha')->dateTime('d/m/Y H:i:s', timezone: self::DISPLAY_TIMEZONE)->sortable(), TextColumn::make('type')->label('Evento')->formatStateUsing(fn (DeliveryRouteEventType $state): string => $state->label())->badge(), TextColumn::make('from_status')->label('Estado anterior')->formatStateUsing(fn (string $state): string => DeliveryRouteStatus::from($state)->label())->badge(), TextColumn::make('to_status')->label('Estado nuevo')->formatStateUsing(fn (string $state): string => DeliveryRouteStatus::from($state)->label())->badge(), TextColumn::make('performer.name')->label('Registrado por'), TextColumn::make('latitude')->label('Latitud')->placeholder('-'), TextColumn::make('longitude')->label('Longitud')->placeholder('-')])->defaultSort('recorded_at', 'desc');
    }
}

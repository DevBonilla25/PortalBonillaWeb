<?php

namespace App\Filament\Resources\LogisticOperations\RelationManagers;

use App\Enums\LogisticOperationAction;
use App\Enums\LogisticOperationStatus;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TransitionsRelationManager extends RelationManager
{
    private const DISPLAY_TIMEZONE = 'America/Guayaquil';

    protected static string $relationship = 'transitions';

    protected static ?string $title = 'Historial del recorrido';

    public function form(Schema $schema): Schema
    {
        return $schema;
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('created_at')->label('Fecha')->dateTime('d/m/Y H:i:s', timezone: self::DISPLAY_TIMEZONE)->sortable(),
            TextColumn::make('action')->label('Acción')->formatStateUsing(fn (string $state): string => LogisticOperationAction::from($state)->label())->badge(),
            TextColumn::make('from_status')->label('Estado anterior')->formatStateUsing(fn (?string $state): ?string => filled($state) ? LogisticOperationStatus::from($state)->label() : null)->badge()->placeholder('-'),
            TextColumn::make('to_status')->label('Estado nuevo')->formatStateUsing(fn (?string $state): ?string => filled($state) ? LogisticOperationStatus::from($state)->label() : null)->badge()->placeholder('-'),
            TextColumn::make('performer.name')->label('Ejecutado por')->placeholder('-'),
            TextColumn::make('notes')->label('Notas')->placeholder('-')->limit(50),
        ])->defaultSort('created_at', 'desc');
    }
}

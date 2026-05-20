<?php

namespace App\Filament\Resources\Tickets\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AssignmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'assignments';

    protected static ?string $title = 'Asignaciones';

    public function form(Schema $schema): Schema
    {
        return $schema;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('assigned_at')
                    ->label('Asignado')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('driver.user.name')
                    ->label('Chofer')
                    ->placeholder('-'),
                TextColumn::make('vehicle.plate')
                    ->label('Vehiculo')
                    ->placeholder('-'),
                TextColumn::make('warehouseUser.name')
                    ->label('Bodeguero')
                    ->placeholder('-'),
                TextColumn::make('assignedBy.name')
                    ->label('Asignado por')
                    ->placeholder('-'),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge(),
                TextColumn::make('assistants.name')
                    ->label('Auxiliares')
                    ->listWithLineBreaks()
                    ->bulleted()
                    ->placeholder('-'),
            ])
            ->defaultSort('assigned_at', 'desc');
    }
}

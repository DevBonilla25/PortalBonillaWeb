<?php

namespace App\Filament\Resources\DeliveryRoutes\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class NoveltiesRelationManager extends RelationManager
{
    protected static string $relationship = 'novelties';

    protected static ?string $title = 'Novedades generales';

    public function form(Schema $schema): Schema
    {
        return $schema;
    }

    public function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('occurred_at')->label('Fecha')->dateTime('d/m/Y H:i')->sortable(), TextColumn::make('reason.name')->label('Motivo')->badge(), TextColumn::make('description')->label('Descripcion')->wrap(), TextColumn::make('reporter.name')->label('Reportada por'), TextColumn::make('status')->label('Estado')->badge()])->defaultSort('occurred_at', 'desc');
    }
}

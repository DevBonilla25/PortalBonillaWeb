<?php

namespace App\Filament\Resources\Tickets\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    private const DISPLAY_TIMEZONE = 'America/Guayaquil';

    protected static string $relationship = 'items';

    protected static ?string $title = 'Productos';

    public function form(Schema $schema): Schema
    {
        return $schema;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('product_name')
            ->columns([
                TextColumn::make('product_code')
                    ->label('Codigo')
                    ->placeholder('-'),
                TextColumn::make('product_name')
                    ->label('Producto')
                    ->searchable(),
                TextColumn::make('quantity')
                    ->label('Cant. ticket'),
                TextColumn::make('loaded_quantity')
                    ->label('Cant. cargada')
                    ->placeholder('-'),
                IconColumn::make('is_loaded')
                    ->label('Cargado')
                    ->boolean(),
                TextColumn::make('loadReviewedBy.name')
                    ->label('Revisado por')
                    ->placeholder('-'),
                TextColumn::make('load_reviewed_at')
                    ->label('Revisado')
                    ->dateTime(timezone: self::DISPLAY_TIMEZONE)
                    ->placeholder('-'),
                TextColumn::make('load_observation')
                    ->label('Observacion')
                    ->limit(40)
                    ->placeholder('-'),
            ]);
    }
}

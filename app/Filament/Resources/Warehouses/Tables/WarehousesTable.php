<?php

namespace App\Filament\Resources\Warehouses\Tables;

use App\Enums\WarehouseType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class WarehousesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Código')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (WarehouseType $state): string => $state->label()),
                TextColumn::make('branch.name')
                    ->label('Sucursal')
                    ->placeholder('—'),
                TextColumn::make('morfeusMappings.external_warehouse_id')
                    ->label('Morfeus')
                    ->badge()
                    ->placeholder('â€”')
                    ->toggleable(),
                IconColumn::make('is_general')
                    ->label('General')
                    ->boolean(),
                IconColumn::make('is_active')
                    ->label('Activa')
                    ->boolean(),
            ])
            ->defaultSort('code')
            ->filters([
                SelectFilter::make('type')
                    ->label('Tipo')
                    ->options(WarehouseType::class),
                TernaryFilter::make('is_general')
                    ->label('Bodega general'),
                TernaryFilter::make('is_active')
                    ->label('Activa'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

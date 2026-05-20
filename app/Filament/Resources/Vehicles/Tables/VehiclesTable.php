<?php

namespace App\Filament\Resources\Vehicles\Tables;

use App\Enums\VehicleStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class VehiclesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label('Codigo')->searchable()->sortable(),
                TextColumn::make('plate')->label('Placa')->searchable()->sortable(),
                TextColumn::make('brand')->label('Marca')->placeholder('-')->searchable(),
                TextColumn::make('model')->label('Modelo')->placeholder('-')->searchable(),
                TextColumn::make('status')->label('Estado')->badge()->sortable(),
                IconColumn::make('is_active')->label('Activo')->boolean(),
            ])
            ->defaultSort('plate')
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options(VehicleStatus::class),
                TernaryFilter::make('is_active')->label('Activo'),
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

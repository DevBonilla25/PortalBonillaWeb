<?php

namespace App\Filament\Resources\DriverProfiles\Tables;

use App\Enums\DriverStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class DriverProfilesTable
{
    private const DISPLAY_TIMEZONE = 'America/Guayaquil';

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')->label('Usuario')->searchable()->sortable(),
                TextColumn::make('employee.display_name')->label('Empleado')->placeholder('-'),
                TextColumn::make('defaultVehicle.plate')->label('Vehiculo')->placeholder('-'),
                TextColumn::make('license_number')->label('Licencia')->placeholder('-')->searchable(),
                TextColumn::make('status')->label('Estado')->badge()->sortable(),
                TextColumn::make('last_connection_at')->label('Ultima conexion')->dateTime(timezone: self::DISPLAY_TIMEZONE)->placeholder('-'),
                IconColumn::make('is_active')->label('Activo')->boolean(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options(DriverStatus::class),
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

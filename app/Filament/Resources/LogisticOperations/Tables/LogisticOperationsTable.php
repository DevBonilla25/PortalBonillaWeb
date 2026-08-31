<?php

namespace App\Filament\Resources\LogisticOperations\Tables;

use App\Enums\LogisticOperationStatus;
use App\Models\DriverProfile;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LogisticOperationsTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')->label('N.º')->sortable(),
            TextColumn::make('plant_name')->label('Planta')->placeholder('-')->searchable(),
            TextColumn::make('destination')->label('Destino')->searchable(),
            TextColumn::make('driver.user.name')->label('Chofer')->placeholder('Sin asignar'),
            TextColumn::make('vehicle.plate')->label('Vehículo')->placeholder('Sin asignar'),
            TextColumn::make('status')->label('Estado')->formatStateUsing(fn (LogisticOperationStatus $state) => $state->label())->color(fn (LogisticOperationStatus $state) => $state->color())->badge()->sortable(),
            TextColumn::make('scheduled_start_at')->label('Programada')->dateTime('d/m/Y H:i')->placeholder('-')->sortable(),
            TextColumn::make('scheduled_arrival_at')->label('Llegada programada')->dateTime('d/m/Y H:i')->placeholder('-')->sortable()->toggleable(),
            TextColumn::make('updated_at')->label('Última actividad')->since()->sortable(),
        ])->defaultSort('updated_at', 'desc')->filters([
            SelectFilter::make('status')->label('Estado')->options(collect(LogisticOperationStatus::cases())->mapWithKeys(fn ($status) => [$status->value => $status->label()])->all()),
            SelectFilter::make('driver_id')->label('Chofer')->options(fn (): array => DriverProfile::query()
                ->with('user')->where('is_active', true)->get()
                ->mapWithKeys(fn (DriverProfile $driver): array => [$driver->id => $driver->user?->name ?? "Chofer #{$driver->id}"])->all()),
        ])->recordActions([ViewAction::make(), EditAction::make()]);
    }
}

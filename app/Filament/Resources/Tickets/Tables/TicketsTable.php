<?php

namespace App\Filament\Resources\Tickets\Tables;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TicketsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('ticket_code')->label('Ticket')->searchable()->sortable(),
                TextColumn::make('guide_number')->label('Guia')->placeholder('-')->searchable(),
                TextColumn::make('customer_name')->label('Cliente')->searchable(),
                TextColumn::make('zone.name')->label('Zona')->placeholder('-')->sortable(),
                TextColumn::make('currentDriver.user.name')->label('Chofer')->placeholder('-'),
                TextColumn::make('currentVehicle.plate')->label('Vehiculo')->placeholder('-'),
                TextColumn::make('status')->label('Estado')->badge()->sortable(),
                TextColumn::make('priority')->label('Prioridad')->badge()->sortable(),
                TextColumn::make('created_at')->label('Creado')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options(TicketStatus::class),
                SelectFilter::make('priority')
                    ->label('Prioridad')
                    ->options(TicketPriority::class),
                SelectFilter::make('zone_id')
                    ->label('Zona')
                    ->relationship('zone', 'name'),
                SelectFilter::make('current_driver_id')
                    ->label('Chofer')
                    ->relationship('currentDriver', 'id')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->user?->name ?? "Chofer #{$record->id}"),
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

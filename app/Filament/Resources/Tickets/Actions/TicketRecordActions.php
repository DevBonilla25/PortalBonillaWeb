<?php

namespace App\Filament\Resources\Tickets\Actions;

use App\Actions\Tickets\AssignTicketResourcesAction;
use App\Actions\Tickets\ChangeTicketStatusAction;
use App\Actions\Tickets\SendTicketToWarehouseAction;
use App\Enums\TicketStatus;
use App\Models\DriverProfile;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Vehicle;
use DomainException;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class TicketRecordActions
{
    public static function sendToWarehouse(): Action
    {
        return Action::make('sendToWarehouse')
            ->label('Enviar a bodega')
            ->icon('heroicon-o-paper-airplane')
            ->color('primary')
            ->requiresConfirmation()
            ->visible(fn (Ticket $record): bool => $record->status === TicketStatus::Created)
            ->action(function (Ticket $record): void {
                try {
                    app(SendTicketToWarehouseAction::class)->execute($record, Auth::user());

                    Notification::make()
                        ->title('Ticket enviado a bodega')
                        ->success()
                        ->send();
                } catch (DomainException $exception) {
                    Notification::make()
                        ->title('No se pudo enviar el ticket')
                        ->body($exception->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    public static function assignResources(): Action
    {
        return Action::make('assignResources')
            ->label('Asignar recursos')
            ->icon('heroicon-o-user-group')
            ->color('warning')
            ->visible(fn (Ticket $record): bool => in_array($record->status, [
                TicketStatus::SentToWarehouse,
                TicketStatus::AssignedToWarehouse,
            ], true))
            ->form([
                Select::make('driver_id')
                    ->label('Chofer')
                    ->options(fn () => DriverProfile::query()
                        ->with('user')
                        ->where('is_active', true)
                        ->get()
                        ->mapWithKeys(fn (DriverProfile $driver) => [
                            $driver->id => $driver->user?->name ?? "Chofer #{$driver->id}",
                        ]))
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('vehicle_id')
                    ->label('Vehiculo')
                    ->options(fn () => Vehicle::query()
                        ->where('is_active', true)
                        ->orderBy('plate')
                        ->pluck('plate', 'id'))
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('warehouse_user_id')
                    ->label('Bodeguero responsable')
                    ->options(fn () => User::query()
                        ->where('is_active', true)
                        ->orderBy('name')
                        ->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->nullable(),
                Select::make('assistant_ids')
                    ->label('Auxiliares')
                    ->options(fn () => User::query()
                        ->where('is_active', true)
                        ->orderBy('name')
                        ->pluck('name', 'id'))
                    ->multiple()
                    ->searchable()
                    ->preload(),
                Textarea::make('internal_observation')
                    ->label('Observacion interna')
                    ->columnSpanFull(),
            ])
            ->action(function (Ticket $record, array $data): void {
                try {
                    app(AssignTicketResourcesAction::class)->execute(
                        ticket: $record,
                        driverId: $data['driver_id'] ?? null,
                        vehicleId: $data['vehicle_id'] ?? null,
                        warehouseUserId: $data['warehouse_user_id'] ?? null,
                        assistantIds: $data['assistant_ids'] ?? [],
                        assignedBy: Auth::user(),
                        internalObservation: $data['internal_observation'] ?? null,
                    );

                    Notification::make()
                        ->title('Recursos asignados')
                        ->success()
                        ->send();
                } catch (DomainException $exception) {
                    Notification::make()
                        ->title('No se pudo asignar recursos')
                        ->body($exception->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    public static function changeStatus(): Action
    {
        return Action::make('changeStatus')
            ->label('Cambiar estado')
            ->icon('heroicon-o-arrow-path')
            ->color('gray')
            ->visible(fn (Ticket $record): bool => $record->status->allowedNextStatuses() !== [])
            ->form([
                Select::make('next_status')
                    ->label('Nuevo estado')
                    ->options(fn (Ticket $record): array => collect($record->status->allowedNextStatuses())
                        ->mapWithKeys(fn (TicketStatus $status) => [$status->value => $status->label()])
                        ->all())
                    ->required(),
                Textarea::make('description')
                    ->label('Observacion')
                    ->columnSpanFull(),
            ])
            ->action(function (Ticket $record, array $data): void {
                try {
                    app(ChangeTicketStatusAction::class)->execute(
                        ticket: $record,
                        nextStatus: TicketStatus::from($data['next_status']),
                        user: Auth::user(),
                        description: $data['description'] ?? null,
                    );

                    Notification::make()
                        ->title('Estado actualizado')
                        ->success()
                        ->send();
                } catch (DomainException $exception) {
                    Notification::make()
                        ->title('No se pudo cambiar el estado')
                        ->body($exception->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }
}

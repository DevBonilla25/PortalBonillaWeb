<?php

namespace App\Filament\Resources\Tickets\Actions;

use App\Actions\Tickets\AssignTicketResourcesAction;
use App\Actions\Tickets\CancelTicketDefinitivelyAction;
use App\Actions\Tickets\ChangeTicketStatusAction;
use App\Actions\Tickets\RescheduleTicketAction;
use App\Actions\Tickets\ReviewTicketLoadingChecklistAction;
use App\Actions\Tickets\SendTicketToWarehouseAction;
use App\Enums\TicketStatus;
use App\Filament\Pages\WarehousePanel;
use App\Models\DriverProfile;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Vehicle;
use App\Support\Tickets\LoadingChecklistForm;
use App\Support\Tickets\LoadingChecklistValidation;
use App\Support\Tickets\TicketAssignmentForm;
use DomainException;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class TicketRecordActions
{
    private const ADMIN_ROLES = ['super_admin', 'admin', 'supervisor'];

    private const CASHIER_ROLES = ['cashier'];

    private const WAREHOUSE_OPERATOR_ROLES = ['warehouse_operator'];

    private const WAREHOUSE_ASSISTANT_ROLES = ['warehouse_assistant'];

    public static function sendToWarehouse(): Action
    {
        return Action::make('sendToWarehouse')
            ->label('Enviar a bodega')
            ->icon('heroicon-o-paper-airplane')
            ->color('primary')
            ->requiresConfirmation()
            ->modalHeading('Enviar ticket a bodega')
            ->modalDescription('El ticket quedara disponible para gestion de bodega.')
            ->modalSubmitActionLabel('Si, enviar')
            ->modalCancelActionLabel('No, volver')
            ->visible(fn (Ticket $record): bool => self::canCashierOperate() && in_array($record->status, [
                TicketStatus::Created,
            ], true))
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

    public static function cancelTicket(): Action
    {
        return Action::make('cancelTicket')
            ->label('Cancelar definitivamente')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Cancelar ticket definitivamente')
            ->modalDescription('Esta acción es terminal. El ticket no podrá asignarse ni enviarse nuevamente a bodega.')
            ->modalSubmitActionLabel('Sí, cancelar definitivamente')
            ->modalCancelActionLabel('No, volver')
            ->schema([
                Textarea::make('reason')->label('Motivo')->required()->maxLength(1500),
            ])
            ->visible(fn (Ticket $record): bool => self::canManageTicketLifecycle() && in_array($record->status, RescheduleTicketAction::allowedStatuses(), true))
            ->action(function (Ticket $record, array $data, Component $livewire): void {
                try {
                    app(CancelTicketDefinitivelyAction::class)->execute($record, $data['reason'], Auth::user());

                    Notification::make()
                        ->title('Ticket cancelado')
                        ->success()
                        ->send();

                    $livewire->redirect(WarehousePanel::getUrl(), navigate: true);
                } catch (DomainException $exception) {
                    Notification::make()
                        ->title('No se pudo cancelar el ticket')
                        ->body($exception->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    public static function rescheduleTicket(): Action
    {
        return Action::make('rescheduleTicket')
            ->label('Reprogramar')
            ->icon('heroicon-o-calendar-days')
            ->color('warning')
            ->modalHeading('Reprogramar ticket')
            ->modalDescription('El ticket volverá inmediatamente a bodega y quedará disponible para una nueva asignación.')
            ->schema([
                Textarea::make('reason')->label('Motivo')->required()->maxLength(1500),
            ])
            ->visible(fn (Ticket $record): bool => self::canManageTicketLifecycle() && in_array($record->status, RescheduleTicketAction::allowedStatuses(), true))
            ->action(function (Ticket $record, array $data, Component $livewire): void {
                try {
                    app(RescheduleTicketAction::class)->execute($record, $data['reason'], Auth::user());
                    $record->refresh();

                    Notification::make()->title('Ticket reprogramado')->success()->send();
                    $livewire->redirect(WarehousePanel::getUrl(), navigate: true);
                } catch (DomainException $exception) {
                    Notification::make()->title('No se pudo reprogramar')->body($exception->getMessage())->danger()->send();
                }
            });
    }

    public static function assignResources(): Action
    {
        return Action::make('assignResources')
            ->label('Asignar recursos')
            ->icon('heroicon-o-user-group')
            ->color('warning')
            ->visible(fn (Ticket $record): bool => self::canWarehouseOperate() && in_array($record->status, [
                TicketStatus::SentToWarehouse,
                TicketStatus::Picking,
                TicketStatus::AssignedToWarehouse,
            ], true))
            ->modalHeading(fn (Ticket $record): string => TicketAssignmentForm::hasExistingAssignment($record)
                ? 'Actualizar asignación'
                : 'Asignar recursos')
            ->fillForm(fn (Ticket $record): array => [
                ...TicketAssignmentForm::defaultState($record),
                'warehouse_user_id' => Auth::id(),
            ])
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
                    ->default(fn (): ?int => Auth::id())
                    ->disabled()
                    ->dehydrated(),
                Select::make('assistant_ids')
                    ->label('Auxiliares')
                    ->options(fn () => User::query()
                        ->where('is_active', true)
                        ->whereHas('roles', fn ($query) => $query
                            ->whereIn('name', self::WAREHOUSE_ASSISTANT_ROLES)
                            ->where('guard_name', 'web'))
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
            ->visible(fn (Ticket $record): bool => self::canWarehouseOperate() && $record->status->allowedNextStatuses() !== [])
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

    public static function reviewLoadingChecklist(): Action
    {
        return Action::make('reviewLoadingChecklist')
            ->label('Revisar carga')
            ->icon('heroicon-o-clipboard-document-check')
            ->color('warning')
            ->visible(fn (Ticket $record): bool => self::canWarehouseOperate() && in_array($record->status, [
                TicketStatus::Loaded,
            ], true) && $record->items()->exists())
            ->fillForm(fn (Ticket $record): array => [
                'items' => $record->items()
                    ->orderBy('id')
                    ->get()
                    ->map(fn ($item): array => [
                        'id' => $item->id,
                        'product_name' => $item->product_name,
                        'quantity' => $item->quantity,
                        'loaded_quantity' => $item->loaded_quantity ?? $item->quantity,
                        'is_loaded' => (bool) $item->is_loaded,
                        'load_observation' => $item->load_observation,
                    ])
                    ->values()
                    ->all(),
            ])
            ->form([
                LoadingChecklistForm::repeater(),
            ])
            ->action(function (Ticket $record, array $data): void {
                try {
                    LoadingChecklistValidation::validateOrThrow($data['items'] ?? []);

                    app(ReviewTicketLoadingChecklistAction::class)->execute(
                        ticket: $record,
                        items: $data['items'] ?? [],
                        reviewedBy: Auth::user(),
                    );

                    Notification::make()
                        ->title('Checklist revisado')
                        ->success()
                        ->send();
                } catch (ValidationException $exception) {
                    $message = collect($exception->errors())->flatten()->first()
                        ?? 'Revisa el checklist de carga.';

                    Notification::make()
                        ->title('Checklist incompleto')
                        ->body($message)
                        ->danger()
                        ->send();

                    throw $exception;
                } catch (DomainException $exception) {
                    Notification::make()
                        ->title('No se pudo revisar la carga')
                        ->body($exception->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    private static function canCashierOperate(): bool
    {
        $user = Auth::user();

        return $user !== null
            && $user->hasAnyRole([...self::ADMIN_ROLES, ...self::CASHIER_ROLES]);
    }

    private static function canWarehouseOperate(): bool
    {
        $user = Auth::user();

        return $user !== null
            && $user->hasAnyRole([...self::ADMIN_ROLES, ...self::WAREHOUSE_OPERATOR_ROLES]);
    }

    private static function canManageTicketLifecycle(): bool
    {
        $user = Auth::user();

        return $user !== null
            && $user->can('Update:Ticket')
            && ! $user->hasAnyRole(['driver', 'external_driver']);
    }
}

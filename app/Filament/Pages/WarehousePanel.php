<?php

namespace App\Filament\Pages;

use App\Actions\Tickets\AssignTicketResourcesAction;
use App\Actions\Tickets\ChangeTicketStatusAction;
use App\Actions\Tickets\ReceiveTicketReturnAction;
use App\Actions\Tickets\ReviewTicketLoadingChecklistAction;
use App\Enums\TicketStatus;
use App\Filament\Concerns\HasLogisticsNavigation;
use App\Filament\Resources\Tickets\TicketResource;
use App\Models\DriverProfile;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\WarehousePanelService;
use App\Support\Tickets\LoadingChecklistForm;
use App\Support\Tickets\LoadingChecklistValidation;
use App\Support\Tickets\TicketAssignmentForm;
use App\Support\Warehouse\WarehousePanelColumn;
use BackedEnum;
use DomainException;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Url;

class WarehousePanel extends Page implements HasActions
{
    use HasLogisticsNavigation;
    use InteractsWithActions;

    private const LOADED_NOTIFICATION_DURATION_MS = 8000;

    private const LOADED_NOTIFICATION_SOUND_PATH = 'sounds/notification-sound.mp3';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static ?string $navigationLabel = 'Panel bodega';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Panel de bodega';

    protected static string $routePath = 'warehouse-panel';

    protected string $view = 'filament.pages.warehouse-panel';

    protected Width|string|null $maxContentWidth = Width::Full;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'bodega')]
    public ?int $warehouseId = null;

    /**
     * @var array<string, int>
     */
    public array $columnLimits = [];

    public ?int $lastNotifiedLoadedEventId = null;

    public ?int $lastNotifiedSentToWarehouseEventId = null;

    public function getHeading(): string|Htmlable
    {
        return 'Panel de bodega';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Panel administrativo · '.now()->translatedFormat('j \d\e F \d\e Y');
    }

    public static function canAccess(): bool
    {
        return app(WarehousePanelService::class)->canAccessPanel(Auth::user());
    }

    public function mount(): void
    {
        $service = app(WarehousePanelService::class);
        $user = Auth::user();

        $this->columnLimits = $service->defaultColumnLimits();

        if ($service->requiresWarehouseAssignment($user)) {
            return;
        }

        $this->warehouseId = $service->effectiveWarehouseId($user, $this->warehouseId)
            ?? array_key_first($service->warehouseOptions($user));

        $this->resetWarehouseNotificationCursors();
    }

    /**
     * @return list<WarehousePanelColumn>
     */
    public function getColumns(): array
    {
        return WarehousePanelColumn::all();
    }

    /**
     * @return Collection<string, Collection<int, Ticket>>
     */
    public function getTicketsByColumnProperty(): Collection
    {
        return app(WarehousePanelService::class)->ticketsByColumn(
            user: Auth::user(),
            search: $this->search,
            warehouseId: $this->warehouseId,
            limits: $this->columnLimits,
        );
    }

    public function loadMore(string $column): void
    {
        $defaultLimits = app(WarehousePanelService::class)->defaultColumnLimits();

        if (! array_key_exists($column, $defaultLimits)) {
            return;
        }

        $this->columnLimits[$column] = ($this->columnLimits[$column] ?? $defaultLimits[$column]) + 20;
    }

    public function visibleTicketsForColumn(string $column, Collection $tickets): Collection
    {
        $limit = $this->columnLimits[$column] ?? null;

        if ($limit === null) {
            return $tickets;
        }

        return $tickets->take($limit);
    }

    public function columnHasMore(string $column, Collection $tickets): bool
    {
        $limit = $this->columnLimits[$column] ?? null;

        return $limit !== null && $tickets->count() > $limit;
    }

    public function requiresWarehouseAssignment(): bool
    {
        return app(WarehousePanelService::class)->requiresWarehouseAssignment(Auth::user());
    }

    public function isWarehouseSelectorLocked(): bool
    {
        return app(WarehousePanelService::class)->effectiveWarehouseId(Auth::user(), null) !== null
            && ! Auth::user()?->hasAnyRole(['super_admin', 'admin', 'supervisor']);
    }

    /**
     * @return array<int, string>
     */
    public function getWarehouseOptionsProperty(): array
    {
        return app(WarehousePanelService::class)->warehouseOptions(Auth::user());
    }

    public function getVisibleZonesProperty(): Collection
    {
        return app(WarehousePanelService::class)->visibleZones(Auth::user(), $this->warehouseId);
    }

    public function updatedWarehouseId(): void
    {
        $this->resetWarehouseNotificationCursors();
    }

    public function pollWarehousePanel(): void
    {
        $this->notifyNewSentToWarehouseTickets();
        $this->notifyNewLoadedTickets();
    }

    public function assignTicketAction(): Action
    {
        return Action::make('assignTicket')
            ->label('Asignar')
            ->icon(Heroicon::OutlinedUserPlus)
            ->color('gray')
            ->outlined()
            ->size('sm')
            ->modalHeading(function (array $arguments): string {
                $ticketId = $arguments['ticket'] ?? null;

                if (! $ticketId) {
                    return 'Asignar recursos';
                }

                $ticket = Ticket::query()->with('latestAssignment')->find($ticketId);

                return $ticket && TicketAssignmentForm::hasExistingAssignment($ticket)
                    ? 'Actualizar asignación'
                    : 'Asignar recursos';
            })
            ->modalSubmitActionLabel('Asignar recursos')
            ->modalCancelActionLabel('Cancelar')
            ->fillForm(function (array $arguments): array {
                $ticketId = $arguments['ticket'] ?? $this->getArguments()['ticket'] ?? null;

                $ticket = Ticket::query()->findOrFail($ticketId);

                return $this->assignmentDefaultState($ticket);
            })
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
                    ->live()
                    ->afterStateUpdated(function (?string $state, Set $set): void {
                        if (blank($state)) {
                            return;
                        }

                        $vehicleId = DriverProfile::query()
                            ->whereKey($state)
                            ->value('default_vehicle_id');

                        if ($vehicleId) {
                            $set('vehicle_id', $vehicleId);
                        }
                    })
                    ->required(),
                Select::make('vehicle_id')
                    ->label('Vehículo')
                    ->options(fn () => Vehicle::query()
                        ->where('is_active', true)
                        ->orderBy('plate')
                        ->pluck('plate', 'id'))
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function (?string $state, Set $set): void {
                        if (blank($state)) {
                            return;
                        }

                        $driverId = DriverProfile::query()
                            ->where('is_active', true)
                            ->where('default_vehicle_id', $state)
                            ->orderBy('id')
                            ->value('id');

                        if ($driverId) {
                            $set('driver_id', $driverId);
                        }
                    })
                    ->required(),
                Hidden::make('warehouse_user_id')
                    ->default(fn (): ?int => Auth::id())
                    ->dehydrated(),
                Placeholder::make('warehouse_user_name')
                    ->label('Bodeguero responsable')
                    ->content(fn (): string => Auth::user()?->name ?? 'Usuario actual'),
                Select::make('assistant_ids')
                    ->label('Auxiliares')
                    ->options(fn () => User::query()
                        ->where('is_active', true)
                        ->role('warehouse_assistant')
                        ->orderBy('name')
                        ->pluck('name', 'id'))
                    ->multiple()
                    ->searchable()
                    ->preload(),
                Textarea::make('internal_observation')
                    ->label('Observación interna')
                    ->columnSpanFull(),
            ])
            ->action(function (array $arguments, array $data): void {
                $ticketId = $arguments['ticket'] ?? $this->getArguments()['ticket'] ?? null;
                $ticket = Ticket::query()->findOrFail($ticketId);

                try {
                    app(AssignTicketResourcesAction::class)->execute(
                        ticket: $ticket,
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

    public function advanceTicketAction(): Action
    {
        return Action::make('advanceTicket')
            ->label('Avanzar')
            ->icon(Heroicon::OutlinedArrowRight)
            ->iconPosition('after')
            ->color('primary')
            ->size('sm')
            ->requiresConfirmation()
            ->modalHeading('Avanzar ticket')
            ->modalDescription('El ticket pasará de preparación a cargando.')
            ->action(function (array $arguments): void {
                $ticketId = $arguments['ticket'] ?? $this->getArguments()['ticket'] ?? null;
                $ticket = Ticket::query()->with(['latestAssignment.assistants', 'currentDriver'])->findOrFail($ticketId);
                $service = app(WarehousePanelService::class);
                $nextStatus = $service->nextAdvanceStatus($ticket);

                if (! $service->canUserAdvanceLoading(Auth::user(), $ticket)) {
                    Notification::make()
                        ->title('No puedes avanzar este ticket')
                        ->body('Solo el auxiliar asignado, el chofer asignado o bodega pueden mover este tramo.')
                        ->danger()
                        ->send();

                    return;
                }

                if (! $nextStatus) {
                    Notification::make()
                        ->title('No hay un siguiente estado disponible')
                        ->body('En cargando usa "Marcar cargado" para dejar la carga lista para validacion.')
                        ->warning()
                        ->send();

                    return;
                }

                try {
                    app(ChangeTicketStatusAction::class)->execute(
                        ticket: $ticket,
                        nextStatus: $nextStatus,
                        user: Auth::user(),
                        description: 'Avance desde panel de bodega.',
                    );

                    Notification::make()
                        ->title('Estado actualizado')
                        ->body("Ticket en estado: {$nextStatus->label()}")
                        ->success()
                        ->send();
                } catch (DomainException $exception) {
                    Notification::make()
                        ->title('No se pudo avanzar el ticket')
                        ->body($exception->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    public function reviewLoadingChecklistAction(): Action
    {
        return Action::make('reviewLoadingChecklist')
            ->label('Validar carga')
            ->icon(Heroicon::OutlinedClipboardDocumentCheck)
            ->color('warning')
            ->size('sm')
            ->modalHeading('Validar carga - checklist')
            ->modalSubmitActionLabel('Validar y despachar')
            ->fillForm(function (array $arguments): array {
                $ticketId = $arguments['ticket'] ?? $this->getArguments()['ticket'] ?? null;
                $ticket = Ticket::query()->with('items')->findOrFail($ticketId);

                return [
                    'items' => $ticket->items
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
                ];
            })
            ->form([
                LoadingChecklistForm::repeater(),
            ])
            ->action(function (array $arguments, array $data): void {
                $ticketId = $arguments['ticket'] ?? $this->getArguments()['ticket'] ?? null;
                $ticket = Ticket::query()->findOrFail($ticketId);

                try {
                    LoadingChecklistValidation::validateOrThrow($data['items'] ?? []);

                    $ticket = app(ReviewTicketLoadingChecklistAction::class)->execute(
                        ticket: $ticket,
                        items: $data['items'] ?? [],
                        reviewedBy: Auth::user(),
                    );

                    Notification::make()
                        ->title('Checklist guardado y ticket despachado')
                        ->body("Ticket {$ticket->ticket_code} en estado: {$ticket->status->label()}")
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

    public function markLoadedAction(): Action
    {
        return Action::make('markLoaded')
            ->label('Marcar cargado')
            ->icon(Heroicon::OutlinedCheckCircle)
            ->color('success')
            ->size('sm')
            ->requiresConfirmation()
            ->modalHeading('Marcar carga como lista')
            ->modalDescription('El jefe de bodega podra validar los productos cargados antes de despachar.')
            ->action(function (array $arguments): void {
                $ticketId = $arguments['ticket'] ?? $this->getArguments()['ticket'] ?? null;
                $ticket = Ticket::query()->with(['latestAssignment.assistants'])->findOrFail($ticketId);
                $user = Auth::user();

                if (! $user || ! app(WarehousePanelService::class)->canUserMarkLoaded($user, $ticket)) {
                    Notification::make()
                        ->title('No puedes marcar este ticket como cargado')
                        ->body('Solo el auxiliar asignado o bodega pueden completar este tramo.')
                        ->danger()
                        ->send();

                    return;
                }

                try {
                    app(ChangeTicketStatusAction::class)->execute(
                        ticket: $ticket,
                        nextStatus: TicketStatus::Loaded,
                        user: $user,
                        description: 'Carga marcada como lista por bodega.',
                    );

                    Notification::make()
                        ->title('Ticket marcado como cargado')
                        ->success()
                        ->send();
                } catch (DomainException $exception) {
                    Notification::make()
                        ->title('No se pudo marcar como cargado')
                        ->body($exception->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    public function receiveReturnAction(): Action
    {
        return Action::make('receiveReturn')
            ->label('Recibir retorno')
            ->icon(Heroicon::OutlinedArrowDownTray)
            ->color('warning')
            ->size('sm')
            ->modalHeading('Confirmar recepción en bodega')
            ->modalSubmitActionLabel('Confirmar recepción')
            ->modalCancelActionLabel('Cancelar')
            ->form([Textarea::make('observation')->label('Resultado de la verificación')->maxLength(1500)])
            ->action(function (array $arguments, array $data): void {
                $ticket = Ticket::query()->findOrFail($arguments['ticket'] ?? $this->getArguments()['ticket'] ?? null);
                try {
                    app(ReceiveTicketReturnAction::class)->execute($ticket, Auth::user(), $data['observation'] ?? null);
                    Notification::make()->title('Retorno recibido en bodega')->success()->send();
                } catch (DomainException $exception) {
                    Notification::make()->title('No se pudo recibir el retorno')->body($exception->getMessage())->danger()->send();
                }
            });
    }

    public function assignTicketButtonHtml(int $ticketId): string
    {
        return ($this->assignTicketAction())(['ticket' => $ticketId])
            ->livewire($this)
            ->toHtml();
    }

    public function advanceTicketButtonHtml(int $ticketId): string
    {
        return ($this->advanceTicketAction())(['ticket' => $ticketId])
            ->livewire($this)
            ->toHtml();
    }

    public function reviewLoadingChecklistButtonHtml(int $ticketId): string
    {
        return ($this->reviewLoadingChecklistAction())(['ticket' => $ticketId])
            ->livewire($this)
            ->toHtml();
    }

    public function markLoadedButtonHtml(int $ticketId): string
    {
        return ($this->markLoadedAction())(['ticket' => $ticketId])
            ->livewire($this)
            ->toHtml();
    }

    /**
     * @return array{
     *     driver_id: int|null,
     *     vehicle_id: int|null,
     *     warehouse_user_id: int|null,
     *     assistant_ids: list<int>,
     *     internal_observation: string|null,
     * }
     */
    public function receiveReturnButtonHtml(int $ticketId): string
    {
        return ($this->receiveReturnAction())(['ticket' => $ticketId])->livewire($this)->toHtml();
    }

    public function canReceiveReturn(Ticket $ticket): bool
    {
        return (bool) Auth::user()?->can('Update:Ticket') && $ticket->status === TicketStatus::Returning;
    }

    private function assignmentDefaultState(Ticket $ticket): array
    {
        $state = TicketAssignmentForm::defaultState($ticket);

        if (blank($state['vehicle_id']) && filled($state['driver_id'])) {
            $state['vehicle_id'] = DriverProfile::query()
                ->whereKey($state['driver_id'])
                ->value('default_vehicle_id');
        }

        if (blank($state['driver_id']) && filled($state['vehicle_id'])) {
            $state['driver_id'] = DriverProfile::query()
                ->where('is_active', true)
                ->where('default_vehicle_id', $state['vehicle_id'])
                ->orderBy('id')
                ->value('id');
        }

        $state['warehouse_user_id'] = Auth::id();

        return $state;
    }

    public function canAssignTicket(Ticket $ticket): bool
    {
        $user = Auth::user();

        return $user?->can('Update:Ticket')
            && app(WarehousePanelService::class)->canUserAssignResources($user, $ticket);
    }

    public function canAdvanceTicket(Ticket $ticket): bool
    {
        return Auth::user() !== null
            && app(WarehousePanelService::class)->canUserAdvanceLoading(Auth::user(), $ticket)
            && app(WarehousePanelService::class)->canAdvance($ticket);
    }

    public function canReviewLoadingChecklist(Ticket $ticket): bool
    {
        $user = Auth::user();

        return $user?->can('Update:Ticket')
            && app(WarehousePanelService::class)->canUserReviewLoadingChecklist($user, $ticket)
            && ! $ticket->isLoadingChecklistReviewed();
    }

    public function canMarkLoaded(Ticket $ticket): bool
    {
        $user = Auth::user();

        return $user?->can('Update:Ticket')
            && app(WarehousePanelService::class)->canUserMarkLoaded($user, $ticket);
    }

    public function ticketViewUrl(Ticket $ticket): string
    {
        return TicketResource::getUrl('view', ['record' => $ticket]);
    }

    public function sentToWarehouseTime(Ticket $ticket): string
    {
        $sentAt = $ticket->latestSentToWarehouseEvent?->occurred_at
            ?? $ticket->sent_to_warehouse_at;

        if (! $sentAt) {
            return 'Sin hora de envío';
        }

        $date = Carbon::parse($sentAt)->timezone('America/Guayaquil');

        if ($date->isToday()) {
            return $date->format('H:i');
        }

        if ($date->isYesterday()) {
            return 'Ayer '.$date->format('H:i');
        }

        return $date->format('d/m/Y H:i');
    }

    private function resetWarehouseNotificationCursors(): void
    {
        $this->resetSentToWarehouseTicketNotificationCursor();
        $this->resetLoadedTicketNotificationCursor();
    }

    private function resetLoadedTicketNotificationCursor(): void
    {
        $this->lastNotifiedLoadedEventId = app(WarehousePanelService::class)
            ->latestLoadedStatusEventIdForPanel(Auth::user(), $this->warehouseId);
    }

    private function resetSentToWarehouseTicketNotificationCursor(): void
    {
        $this->lastNotifiedSentToWarehouseEventId = app(WarehousePanelService::class)
            ->latestSentToWarehouseEventIdForPanel(Auth::user(), $this->warehouseId);
    }

    private function notifyNewSentToWarehouseTickets(): void
    {
        $service = app(WarehousePanelService::class);
        $events = $service->sentToWarehouseEventsForPanel(
            user: Auth::user(),
            warehouseId: $this->warehouseId,
            afterEventId: $this->lastNotifiedSentToWarehouseEventId,
        );

        foreach ($events as $event) {
            $ticket = $event->ticket;

            if (! $ticket) {
                continue;
            }

            $title = 'Ticket recibido en bodega';
            $body = "El ticket {$ticket->ticket_code} llego desde caja y esta en Recibidos.";

            Notification::make()
                ->title($title)
                ->body($body)
                ->duration(self::LOADED_NOTIFICATION_DURATION_MS)
                ->info()
                ->send();

            $this->dispatch(
                'warehouse-ticket-received',
                ticketId: $ticket->id,
                title: $title,
                body: $body,
                soundUrl: asset(self::LOADED_NOTIFICATION_SOUND_PATH),
            );

            $this->lastNotifiedSentToWarehouseEventId = $event->id;
        }
    }

    private function notifyNewLoadedTickets(): void
    {
        $service = app(WarehousePanelService::class);
        $events = $service->loadedStatusEventsForPanel(
            user: Auth::user(),
            warehouseId: $this->warehouseId,
            afterEventId: $this->lastNotifiedLoadedEventId,
        );

        foreach ($events as $event) {
            $ticket = $event->ticket;

            if (! $ticket) {
                continue;
            }

            Notification::make()
                ->title('Ticket cargado')
                ->body("El ticket {$ticket->ticket_code} está listo para validación y despacho.")
                ->duration(self::LOADED_NOTIFICATION_DURATION_MS)
                ->success()
                ->send();

            $this->dispatch(
                'warehouse-ticket-loaded',
                ticketId: $ticket->id,
                title: 'Ticket cargado',
                body: "El ticket {$ticket->ticket_code} está listo para validación y despacho.",
                soundUrl: asset(self::LOADED_NOTIFICATION_SOUND_PATH),
            );

            $this->lastNotifiedLoadedEventId = $event->id;
        }
    }
}

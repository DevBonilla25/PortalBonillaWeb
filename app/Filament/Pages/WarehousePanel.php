<?php

namespace App\Filament\Pages;

use App\Actions\Tickets\AssignTicketResourcesAction;
use App\Actions\Tickets\ChangeTicketStatusAction;
use App\Actions\Tickets\ReviewTicketLoadingChecklistAction;
use App\Enums\TicketPriority;
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
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Url;

class WarehousePanel extends Page implements HasActions
{
    use HasLogisticsNavigation;
    use InteractsWithActions;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static ?string $navigationLabel = 'Panel bodega';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Panel de bodega';

    protected static string $routePath = 'warehouse-panel';

    protected string $view = 'filament.pages.warehouse-panel';

    protected Width|string|null $maxContentWidth = Width::Full;

    #[Url(as: 'q')]
    public string $search = '';

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
        $user = Auth::user();

        return $user !== null && $user->can('ViewAny:Ticket');
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
        );
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
            ->fillForm(function (array $arguments): array {
                $ticketId = $arguments['ticket'] ?? $this->getArguments()['ticket'] ?? null;

                $ticket = Ticket::query()->findOrFail($ticketId);

                return TicketAssignmentForm::defaultState($ticket);
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
                    ->required(),
                Select::make('vehicle_id')
                    ->label('Vehículo')
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
                        ->body('En cargando usa "Cerrar carga" para revisar el checklist y despachar.')
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
            ->label('Cerrar carga')
            ->icon(Heroicon::OutlinedClipboardDocumentCheck)
            ->color('warning')
            ->size('sm')
            ->modalHeading('Cerrar carga — checklist')
            ->modalSubmitActionLabel('Guardar y despachar')
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

    public function priorityColor(TicketPriority $priority): string
    {
        return match ($priority) {
            TicketPriority::High, TicketPriority::Urgent => 'danger',
            TicketPriority::Normal => 'warning',
            TicketPriority::Low => 'gray',
        };
    }

    public function priorityLabel(TicketPriority $priority): string
    {
        return match ($priority) {
            TicketPriority::Normal => 'Media',
            default => $priority->label(),
        };
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

    public function canAssignTicket(Ticket $ticket): bool
    {
        return Auth::user()?->can('Update:Ticket')
            && app(WarehousePanelService::class)->canAssign($ticket);
    }

    public function canAdvanceTicket(Ticket $ticket): bool
    {
        return Auth::user() !== null
            && app(WarehousePanelService::class)->canUserAdvanceLoading(Auth::user(), $ticket)
            && app(WarehousePanelService::class)->canAdvance($ticket);
    }

    public function canReviewLoadingChecklist(Ticket $ticket): bool
    {
        return Auth::user()?->can('Update:Ticket')
            && app(WarehousePanelService::class)->canReviewLoadingChecklist($ticket)
            && ! $ticket->isLoadingChecklistReviewed();
    }

    public function ticketViewUrl(Ticket $ticket): string
    {
        return TicketResource::getUrl('view', ['record' => $ticket]);
    }
}

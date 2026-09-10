<?php

namespace App\Filament\Resources\Tickets\Pages;

use App\Filament\Resources\Tickets\TicketResource;
use App\Services\WarehousePanelService;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class ListTickets extends ListRecords
{
    protected static string $resource = TicketResource::class;

    private const WAREHOUSE_NOTIFICATION_POLLING_INTERVAL = '5s';

    private const WAREHOUSE_NOTIFICATION_DURATION_MS = 8000;

    protected Width|string|null $maxContentWidth = Width::Full;

    public ?int $lastNotifiedSentToWarehouseEventId = null;

    public ?int $sentToWarehouseNotificationWarehouseId = null;

    public bool $sentToWarehouseNotificationCursorInitialized = false;

    public function mount(): void
    {
        parent::mount();

        $this->resetSentToWarehouseTicketNotificationCursor();
    }

    public function render(): View
    {
        $this->resetSentToWarehouseTicketNotificationCursorIfScopeChanged();
        $this->notifyNewSentToWarehouseTickets();

        return parent::render();
    }

    protected function getTablePollingInterval(): ?string
    {
        return self::WAREHOUSE_NOTIFICATION_POLLING_INTERVAL;
    }

    public function getTitle(): string|Htmlable
    {
        return 'Gestión de tickets';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Panel administrativo · '.now()->translatedFormat('j \d\e F \d\e Y');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nuevo ticket'),
        ];
    }

    private function resetSentToWarehouseTicketNotificationCursor(): void
    {
        $this->sentToWarehouseNotificationWarehouseId = $this->selectedWarehouseId();
        $this->sentToWarehouseNotificationCursorInitialized = true;

        $this->lastNotifiedSentToWarehouseEventId = app(WarehousePanelService::class)
            ->latestSentToWarehouseEventIdForPanel(Auth::user(), $this->sentToWarehouseNotificationWarehouseId);
    }

    private function resetSentToWarehouseTicketNotificationCursorIfScopeChanged(): void
    {
        if (! $this->sentToWarehouseNotificationCursorInitialized
            || $this->sentToWarehouseNotificationWarehouseId !== $this->selectedWarehouseId()) {
            $this->resetSentToWarehouseTicketNotificationCursor();
        }
    }

    private function notifyNewSentToWarehouseTickets(): void
    {
        $events = app(WarehousePanelService::class)->sentToWarehouseEventsForPanel(
            user: Auth::user(),
            warehouseId: $this->selectedWarehouseId(),
            afterEventId: $this->lastNotifiedSentToWarehouseEventId,
        );

        foreach ($events as $event) {
            $ticket = $event->ticket;

            if (! $ticket) {
                continue;
            }

            Notification::make()
                ->title('Ticket recibido en bodega')
                ->body("El ticket {$ticket->ticket_code} llegó desde caja y está en Recibidos.")
                ->duration(self::WAREHOUSE_NOTIFICATION_DURATION_MS)
                ->info()
                ->send();

            $this->lastNotifiedSentToWarehouseEventId = $event->id;
        }
    }

    private function selectedWarehouseId(): ?int
    {
        $warehouseFilter = $this->tableFilters['warehouse_id']['value'] ?? null;

        return filled($warehouseFilter) ? (int) $warehouseFilter : null;
    }
}

<?php

namespace App\Livewire;

use App\Models\Ticket;
use App\Models\TicketEvent;
use App\Services\WarehousePanelService;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class WarehouseOperationalNotifications extends Component
{
    private const NOTIFICATION_DURATION_MS = 8000;

    private const NOTIFICATION_SOUND_PATH = 'sounds/notification-sound.mp3';

    public ?int $warehouseId = null;

    public ?int $lastNotifiedSentToWarehouseEventId = null;

    public ?int $lastNotifiedLoadedEventId = null;

    public function mount(): void
    {
        $this->resetNotificationScope();
    }

    public function pollWarehouseOperationalNotifications(): void
    {
        $currentWarehouseId = $this->notificationWarehouseId();

        if ($currentWarehouseId === null) {
            $this->resetNotificationScope();

            return;
        }

        if ($this->warehouseId !== $currentWarehouseId) {
            $this->resetNotificationScope();

            return;
        }

        $this->notifyNewSentToWarehouseTickets();
        $this->notifyNewLoadedTickets();
    }

    public function render(): View
    {
        return view('livewire.warehouse-operational-notifications');
    }

    private function resetNotificationScope(): void
    {
        $this->warehouseId = $this->notificationWarehouseId();

        if ($this->warehouseId === null) {
            $this->lastNotifiedSentToWarehouseEventId = null;
            $this->lastNotifiedLoadedEventId = null;

            return;
        }

        $service = app(WarehousePanelService::class);

        $this->lastNotifiedSentToWarehouseEventId = $service
            ->latestSentToWarehouseEventIdForPanel(Auth::user(), $this->warehouseId);
        $this->lastNotifiedLoadedEventId = $service
            ->latestLoadedStatusEventIdForPanel(Auth::user(), $this->warehouseId);
    }

    private function notificationWarehouseId(): ?int
    {
        $user = Auth::user();

        if (! $user || ! app(WarehousePanelService::class)->canAccessPanel($user)) {
            return null;
        }

        return app(WarehousePanelService::class)->employeeWarehouseId($user);
    }

    private function notifyNewSentToWarehouseTickets(): void
    {
        $events = app(WarehousePanelService::class)->sentToWarehouseEventsForPanel(
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
            $body = "El ticket {$ticket->ticket_code} llegó desde caja y está en Recibidos.";

            Notification::make()
                ->title($title)
                ->body($body)
                ->duration(self::NOTIFICATION_DURATION_MS)
                ->info()
                ->send();

            $this->dispatchWarehouseNotification($event, $ticket, $title, $body, 'warehouse-ticket-received');

            $this->lastNotifiedSentToWarehouseEventId = $event->id;
        }
    }

    private function notifyNewLoadedTickets(): void
    {
        $events = app(WarehousePanelService::class)->loadedStatusEventsForPanel(
            user: Auth::user(),
            warehouseId: $this->warehouseId,
            afterEventId: $this->lastNotifiedLoadedEventId,
        );

        foreach ($events as $event) {
            $ticket = $event->ticket;

            if (! $ticket) {
                continue;
            }

            $title = 'Ticket cargado';
            $body = "El ticket {$ticket->ticket_code} está listo para validación y despacho.";

            Notification::make()
                ->title($title)
                ->body($body)
                ->duration(self::NOTIFICATION_DURATION_MS)
                ->success()
                ->send();

            $this->dispatchWarehouseNotification($event, $ticket, $title, $body, 'warehouse-ticket-loaded');

            $this->lastNotifiedLoadedEventId = $event->id;
        }
    }

    private function dispatchWarehouseNotification(
        TicketEvent $event,
        Ticket $ticket,
        string $title,
        string $body,
        string $type,
    ): void {
        $this->dispatch(
            'warehouse-operational-notification',
            eventId: $event->id,
            ticketId: $ticket->id,
            type: $type,
            title: $title,
            body: $body,
            soundUrl: asset(self::NOTIFICATION_SOUND_PATH),
        );
    }
}

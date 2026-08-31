<?php

namespace App\Services;

use App\Enums\TicketStatus;
use App\Models\DeliveryRoute;
use App\Models\DriverFcmToken;
use App\Models\DriverNotification;
use App\Models\PickupOrder;
use App\Models\Ticket;
use Illuminate\Support\Facades\Log;

class DriverPushNotificationService
{
    public function __construct(private readonly FirebaseCloudMessagingService $firebase) {}

    public function sendTicketAssigned(Ticket $ticket): void
    {
        $this->sendTicketStatusNotification(ticket: $ticket, type: 'ticket_assigned', status: 'assigned', title: 'Ticket asignado', body: "Se te asigno el ticket {$ticket->ticket_code}.");
    }

    public function sendTicketDispatched(Ticket $ticket): void
    {
        $this->sendTicketStatusNotification(ticket: $ticket, type: 'ticket_dispatched', status: TicketStatus::Dispatched->value, title: 'Ticket despachado', body: "El ticket {$ticket->ticket_code} esta listo para entrega.");
    }

    public function sendPickupAssigned(PickupOrder $pickup, ?DeliveryRoute $route = null): void
    {
        $pickup->loadMissing('driver.fcmTokens', 'routeTask');
        $driver = $pickup->driver;
        if (! $driver) {
            return;
        }

        $addedToRoute = $route !== null;
        $title = 'Nuevo retiro asignado';
        $body = "Debes retirar mercaderia en {$pickup->pickup_name}.";
        $type = $addedToRoute ? 'pickup_added_to_route' : 'pickup_assigned';
        $data = [
            'type' => $type, 'pickup_order_id' => $pickup->id, 'pickup_code' => $pickup->code,
            'status' => $pickup->status->value, 'delivery_route_id' => $route?->id,
            'sequence' => $pickup->routeTask?->sequence,
        ];
        $notification = DriverNotification::query()->create([
            'driver_profile_id' => $driver->id, 'pickup_order_id' => $pickup->id, 'type' => $type,
            'title' => $title, 'body' => $body, 'data' => $data,
        ]);
        $sent = false;
        $driver->fcmTokens->each(function (DriverFcmToken $token) use ($data, $title, $body, &$sent): void {
            if ($this->firebase->sendToToken(token: $token->token, title: $title, body: $body, data: $data)) {
                $sent = true;
            }
        });
        if ($sent) {
            $notification->forceFill(['sent_at' => now()])->save();
        }
    }

    private function sendTicketStatusNotification(Ticket $ticket, string $type, string $status, string $title, string $body): void
    {
        $ticket->loadMissing('currentDriver.fcmTokens');
        $driver = $ticket->currentDriver;
        if (! $driver) {
            Log::info('Push chofer omitido: ticket sin chofer asignado.', ['ticket_id' => $ticket->id, 'type' => $type]);

            return;
        }
        $data = ['type' => $type, 'ticket_id' => $ticket->id, 'ticket_code' => $ticket->ticket_code, 'status' => $status];
        $notification = DriverNotification::query()->create(['driver_profile_id' => $driver->id, 'ticket_id' => $ticket->id, 'type' => $type, 'title' => $title, 'body' => $body, 'data' => $data]);
        $tokens = $driver->fcmTokens;
        if ($tokens->isEmpty()) {
            Log::info('Push chofer omitido: chofer sin tokens FCM.', ['ticket_id' => $ticket->id, 'driver_id' => $driver->id, 'type' => $type, 'notification_id' => $notification->id]);

            return;
        }
        $sent = false;
        $tokens->each(function (DriverFcmToken $token) use ($data, $title, $body, &$sent): void {
            if ($this->firebase->sendToToken(token: $token->token, title: $title, body: $body, data: $data)) {
                $sent = true;
            }
        });
        if ($sent) {
            $notification->forceFill(['sent_at' => now()])->save();
        }
    }
}

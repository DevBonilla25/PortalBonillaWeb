<?php

namespace App\Services;

use App\Enums\TicketStatus;
use App\Models\DriverFcmToken;
use App\Models\DriverNotification;
use App\Models\Ticket;
use Illuminate\Support\Facades\Log;

class DriverPushNotificationService
{
    public function __construct(
        private readonly FirebaseCloudMessagingService $firebase,
    ) {}

    public function sendTicketAssigned(Ticket $ticket): void
    {
        $this->sendTicketStatusNotification(
            ticket: $ticket,
            type: 'ticket_assigned',
            status: 'assigned',
            title: 'Ticket asignado',
            body: "Se te asigno el ticket {$ticket->ticket_code}.",
        );
    }

    public function sendTicketDispatched(Ticket $ticket): void
    {
        $this->sendTicketStatusNotification(
            ticket: $ticket,
            type: 'ticket_dispatched',
            status: TicketStatus::Dispatched->value,
            title: 'Ticket despachado',
            body: "El ticket {$ticket->ticket_code} esta listo para entrega.",
        );
    }

    private function sendTicketStatusNotification(
        Ticket $ticket,
        string $type,
        string $status,
        string $title,
        string $body,
    ): void {
        $ticket->loadMissing('currentDriver.fcmTokens');

        $driver = $ticket->currentDriver;

        if (! $driver) {
            Log::info('Push chofer omitido: ticket sin chofer asignado.', [
                'ticket_id' => $ticket->id,
                'type' => $type,
            ]);

            return;
        }

        $data = [
            'type' => $type,
            'ticket_id' => $ticket->id,
            'ticket_code' => $ticket->ticket_code,
            'status' => $status,
        ];

        $notification = DriverNotification::query()->create([
            'driver_profile_id' => $driver->id,
            'ticket_id' => $ticket->id,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'data' => $data,
        ]);

        $tokens = $driver->fcmTokens;

        if ($tokens->isEmpty()) {
            Log::info('Push chofer omitido: chofer sin tokens FCM.', [
                'ticket_id' => $ticket->id,
                'driver_id' => $driver->id,
                'type' => $type,
                'notification_id' => $notification->id,
            ]);

            return;
        }

        $sent = false;

        $tokens->each(function (DriverFcmToken $token) use ($data, $title, $body, &$sent): void {
            if ($this->firebase->sendToToken(
                token: $token->token,
                title: $title,
                body: $body,
                data: $data,
            )) {
                $sent = true;
            }
        });

        if ($sent) {
            $notification->forceFill(['sent_at' => now()])->save();
        }
    }
}

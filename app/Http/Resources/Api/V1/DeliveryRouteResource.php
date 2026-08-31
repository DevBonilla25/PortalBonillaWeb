<?php

namespace App\Http\Resources\Api\V1;

use App\Enums\PickupOrderStatus;
use App\Enums\TicketStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryRouteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'status' => $this->status->value, 'warehouse_id' => $this->warehouse_id,
            'driver_id' => $this->driver_id, 'vehicle_id' => $this->vehicle_id,
            'scheduled_start_at' => $this->scheduled_start_at?->toISOString(), 'started_at' => $this->started_at?->toISOString(),
            'returning_at' => $this->returning_at?->toISOString(), 'arrived_warehouse_at' => $this->arrived_warehouse_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(), 'cancelled_at' => $this->cancelled_at?->toISOString(), 'notes' => $this->notes,
            'tasks' => $this->whenLoaded('tasks', fn () => $this->tasks->map(function ($task) {
                $pickup = $task->pickupOrder ? PickupOrderResource::make($task->pickupOrder) : null;
                $isResolved = $task->ticket
                    ? in_array($task->ticket->status, [TicketStatus::Delivered, TicketStatus::ArrivedBack, TicketStatus::PendingReassignment, TicketStatus::Cancelled], true)
                    : in_array($task->pickupOrder?->status, [PickupOrderStatus::ReceivedAtWarehouse, PickupOrderStatus::Completed, PickupOrderStatus::Failed, PickupOrderStatus::Cancelled], true);
                $requiresDriverAction = $task->ticket
                    ? (int) $task->ticket->current_driver_id === (int) $this->driver_id
                        && in_array($task->ticket->status, [TicketStatus::InRoute, TicketStatus::AtDestination, TicketStatus::Unloading], true)
                    : (int) $task->pickupOrder?->driver_id === (int) $this->driver_id
                        && in_array($task->pickupOrder?->status, [PickupOrderStatus::Assigned, PickupOrderStatus::EnRoute, PickupOrderStatus::AtPickup, PickupOrderStatus::Loading, PickupOrderStatus::PickedUp], true);

                return [
                    'id' => $task->id, 'sequence' => $task->sequence, 'type' => $task->task_type,
                    'is_resolved' => $isResolved, 'requires_driver_action' => $requiresDriverAction,
                    'ticket' => $task->ticket ? [
                        'id' => $task->ticket->id, 'ticket_code' => $task->ticket->ticket_code,
                        'customer_name' => $task->ticket->customer_name, 'delivery_address' => $task->ticket->delivery_address,
                        'status' => $task->ticket->status->value, 'status_label' => $task->ticket->status->label(),
                    ] : null,
                    'pickup_order' => $pickup,
                ];
            })),
            'tickets' => $this->whenLoaded('tickets', fn () => $this->tickets->map(fn ($ticket) => [
                'id' => $ticket->id, 'ticket_code' => $ticket->ticket_code, 'customer_name' => $ticket->customer_name,
                'delivery_address' => $ticket->delivery_address, 'status' => $ticket->status->value,
                'status_label' => $ticket->status->label(), 'sequence' => $ticket->pivot->sequence,
            ])),
            'events' => $this->whenLoaded('events'),
            'novelties' => $this->whenLoaded('novelties', fn () => $this->novelties->map(fn ($novelty) => [
                ...$novelty->toArray(), 'reason' => $novelty->reason,
                'photos' => MediaAttachmentResource::collection($novelty->mediaAttachments),
            ])),
        ];
    }
}

<?php

namespace App\Http\Resources\Api\V1;

use App\Enums\TicketStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DriverTicketResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $allowedNextStatuses = collect($this->status?->allowedNextStatuses() ?? []);

        return [
            'id' => $this->id,
            'ticket_code' => $this->ticket_code,
            'guide_number' => $this->guide_number,
            'customer_name' => $this->customer_name,
            'customer_phone' => $this->customer_phone,
            'customer_phone_2' => $this->customer_phone_2,
            'delivery_address' => $this->delivery_address,
            'delivery_reference' => $this->delivery_reference,
            'delivery_type' => $this->delivery_type?->value,
            'google_maps_url' => $this->google_maps_url,
            'is_rescheduled' => $this->rescheduled_count > 0,
            'rescheduled_count' => $this->rescheduled_count,
            'last_rescheduled_at' => $this->last_rescheduled_at?->toISOString(),
            'reschedule_reason' => $this->reschedule_reason,
            'priority' => $this->priority?->value,
            'priority_label' => $this->priority?->label(),
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'mobile_bucket' => $this->mobileBucket(),
            'allowed_next_statuses' => $allowedNextStatuses
                ->map(fn ($status): array => [
                    'value' => $status->value,
                    'label' => $status->label(),
                ])
                ->values(),
            'zone' => $this->whenLoaded('zone', fn (): ?array => $this->zone ? [
                'id' => $this->zone->id,
                'name' => $this->zone->name,
                'code' => $this->zone->code,
            ] : null),
            'subzone' => $this->whenLoaded('subzone', fn (): ?array => $this->subzone ? [
                'id' => $this->subzone->id,
                'name' => $this->subzone->name,
            ] : null),
            'warehouse' => $this->whenLoaded('warehouse', fn (): ?array => $this->warehouse ? [
                'id' => $this->warehouse->id,
                'name' => $this->warehouse->name,
                'code' => $this->warehouse->code,
            ] : null),
            'vehicle' => $this->whenLoaded('currentVehicle', fn (): ?array => $this->currentVehicle ? [
                'id' => $this->currentVehicle->id,
                'plate' => $this->currentVehicle->plate,
                'code' => $this->currentVehicle->code,
            ] : null),
            'items_count' => $this->whenCounted('items'),
            'items' => TicketItemResource::collection($this->whenLoaded('items')),
            'events' => TicketEventResource::collection($this->whenLoaded('events')),
            'delivery_evidences' => DeliveryEvidenceResource::collection($this->whenLoaded('deliveryEvidences')),
            'novelties' => TicketNoveltyResource::collection($this->whenLoaded('novelties')),
            'assigned_at' => $this->assigned_at?->toISOString(),
            'dispatched_at' => $this->dispatched_at?->toISOString(),
            'delivered_at' => $this->delivered_at?->toISOString(),
            'returned_at' => $this->returned_at?->toISOString(),
            'closed_at' => $this->closed_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    private function mobileBucket(): string
    {
        return match ($this->status) {
            TicketStatus::AssignedToWarehouse,
            TicketStatus::Picking,
            TicketStatus::Loading,
            TicketStatus::Loaded,
            TicketStatus::Dispatched => 'assigned',
            TicketStatus::InRoute => 'in_route',
            TicketStatus::DeliveryFailed,
            TicketStatus::Returning => 'issue',
            TicketStatus::Delivered,
            TicketStatus::ArrivedBack,
            TicketStatus::Cancelled => 'history',
            default => 'other',
        };
    }
}

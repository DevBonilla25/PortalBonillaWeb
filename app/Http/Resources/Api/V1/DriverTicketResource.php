<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DriverTicketResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ticket_code' => $this->ticket_code,
            'guide_number' => $this->guide_number,
            'customer_name' => $this->customer_name,
            'customer_phone' => $this->customer_phone,
            'customer_phone_2' => $this->customer_phone_2,
            'delivery_address' => $this->delivery_address,
            'delivery_reference' => $this->delivery_reference,
            'priority' => $this->priority?->value,
            'priority_label' => $this->priority?->label(),
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'allowed_next_statuses' => collect($this->status?->allowedNextStatuses() ?? [])
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
}

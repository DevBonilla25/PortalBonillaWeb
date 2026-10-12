<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PickupOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'code' => $this->code, 'status' => $this->status->value,
            'status_label' => $this->status->label(), 'priority' => $this->priority->value,
            'warehouse_id' => $this->warehouse_id, 'driver_id' => $this->driver_id, 'vehicle_id' => $this->vehicle_id,
            'pickup_name' => $this->pickup_name, 'pickup_address' => $this->pickup_address,
            'pickup_reference' => $this->pickup_reference, 'contact_name' => $this->contact_name,
            'contact_phone' => $this->contact_phone, 'google_maps_url' => $this->google_maps_url,
            'item_description' => $this->item_description, 'notes' => $this->notes, 'failure_reason' => $this->failure_reason,
            'scheduled_at' => $this->scheduled_at?->toISOString(), 'en_route_at' => $this->en_route_at?->toISOString(),
            'arrived_at' => $this->arrived_at?->toISOString(), 'loading_at' => $this->loading_at?->toISOString(),
            'picked_up_at' => $this->picked_up_at?->toISOString(), 'transporting_at' => $this->transporting_at?->toISOString(),
            'received_at' => $this->received_at?->toISOString(), 'completed_at' => $this->completed_at?->toISOString(),
            'events' => $this->whenLoaded('events'),
            'photos' => MediaAttachmentResource::collection($this->whenLoaded('mediaAttachments')),
        ];
    }
}

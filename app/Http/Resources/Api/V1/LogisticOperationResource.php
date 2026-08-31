<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LogisticOperationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'type' => $this->type, 'status' => $this->status->value,
            'driver_id' => $this->driver_id, 'vehicle_id' => $this->vehicle_id,
            'origin' => $this->origin, 'destination' => $this->destination, 'plant_name' => $this->plant_name,
            'scheduled_start_at' => $this->scheduled_start_at?->toISOString(), 'scheduled_arrival_at' => $this->scheduled_arrival_at?->toISOString(), 'started_at' => $this->started_at?->toISOString(),
            'arrived_plant_at' => $this->arrived_plant_at?->toISOString(), 'queue_started_at' => $this->queue_started_at?->toISOString(),
            'plant_entry_at' => $this->plant_entry_at?->toISOString(), 'loading_started_at' => $this->loading_started_at?->toISOString(),
            'loading_finished_at' => $this->loading_finished_at?->toISOString(), 'left_plant_at' => $this->left_plant_at?->toISOString(),
            'arrived_origin_at' => $this->arrived_origin_at?->toISOString(), 'unloading_started_at' => $this->unloading_started_at?->toISOString(),
            'unloading_finished_at' => $this->unloading_finished_at?->toISOString(), 'finished_at' => $this->finished_at?->toISOString(),
            'cancelled_at' => $this->cancelled_at?->toISOString(), 'notes' => $this->notes,
            'incidents' => $this->whenLoaded('incidents'), 'attachments' => MediaAttachmentResource::collection($this->whenLoaded('mediaAttachments')),
            'transitions' => $this->whenLoaded('transitions'),
            'stops' => $this->whenLoaded('stops'),
        ];
    }
}

<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LocationPointResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ticket_id' => $this->ticket_id,
            'driver_id' => $this->driver_id,
            'vehicle_id' => $this->vehicle_id,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'accuracy' => $this->accuracy,
            'speed' => $this->speed,
            'heading' => $this->heading,
            'battery_level' => $this->battery_level,
            'recorded_at' => $this->recorded_at?->toISOString(),
            'received_at' => $this->received_at?->toISOString(),
            'source' => $this->source,
            'local_event_id' => $this->local_event_id,
            'metadata' => $this->metadata,
        ];
    }
}

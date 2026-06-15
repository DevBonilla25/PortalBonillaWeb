<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketEventResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event_type' => $this->event_type?->value,
            'event_label' => $this->event_type?->label(),
            'previous_status' => $this->previous_status?->value,
            'new_status' => $this->new_status?->value,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'accuracy' => $this->accuracy,
            'occurred_at' => $this->occurred_at?->toISOString(),
            'received_at' => $this->received_at?->toISOString(),
            'source' => $this->source,
            'connection_status' => $this->connection_status,
            'local_event_id' => $this->local_event_id,
            'description' => $this->description,
            'metadata' => $this->metadata,
        ];
    }
}

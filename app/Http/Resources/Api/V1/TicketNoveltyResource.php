<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class TicketNoveltyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ticket_id' => $this->ticket_id,
            'reported_by' => $this->reported_by,
            'driver_id' => $this->driver_id,
            'novelty_reason_id' => $this->novelty_reason_id,
            'reason' => $this->whenLoaded('reason', fn (): ?array => $this->reason ? [
                'id' => $this->reason->id,
                'code' => $this->reason->code,
                'name' => $this->reason->name,
                'requires_photo' => $this->reason->requires_photo,
            ] : null),
            'novelty_type' => $this->novelty_type,
            'description' => $this->description,
            'photo_url' => $this->photo_path ? Storage::disk('public')->url($this->photo_path) : null,
            'status' => $this->status,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'accuracy' => $this->accuracy,
            'occurred_at' => $this->occurred_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}

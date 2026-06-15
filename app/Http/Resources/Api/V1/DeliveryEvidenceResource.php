<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class DeliveryEvidenceResource extends JsonResource
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
            'received_by_name' => $this->received_by_name,
            'received_by_identification' => $this->received_by_identification,
            'photo_url' => $this->photo_path ? Storage::disk('public')->url($this->photo_path) : null,
            'signature_url' => $this->signature_path ? Storage::disk('public')->url($this->signature_path) : null,
            'observation' => $this->observation,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'accuracy' => $this->accuracy,
            'occurred_at' => $this->occurred_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}

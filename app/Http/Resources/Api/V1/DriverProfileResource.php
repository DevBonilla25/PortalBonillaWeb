<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DriverProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'license_number' => $this->license_number,
            'license_type' => $this->license_type,
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'is_active' => (bool) $this->is_active,
            'last_latitude' => $this->last_latitude,
            'last_longitude' => $this->last_longitude,
            'last_location_at' => $this->last_location_at?->toISOString(),
            'last_connection_at' => $this->last_connection_at?->toISOString(),
        ];
    }
}

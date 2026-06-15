<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_code' => $this->product_code,
            'product_name' => $this->product_name,
            'quantity' => $this->quantity,
            'unit' => $this->unit,
            'observations' => $this->observations,
            'is_loaded' => (bool) $this->is_loaded,
            'loaded_quantity' => $this->loaded_quantity,
            'load_observation' => $this->load_observation,
        ];
    }
}

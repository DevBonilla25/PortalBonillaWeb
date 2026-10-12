<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreDeliveryRouteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'driver_id' => ['required', 'integer', 'exists:driver_profiles,id'],
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
            'scheduled_start_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'ticket_ids' => ['required', 'array', 'min:1'],
            'ticket_ids.*' => ['required', 'integer', 'distinct', 'exists:tickets,id'],
        ];
    }
}

<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreLogisticOperationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'driver_id' => ['nullable', 'integer', 'exists:driver_profiles,id'],
            'vehicle_id' => ['nullable', 'integer', 'exists:vehicles,id'],
            'origin' => ['required', 'string', 'max:255'],
            'destination' => ['required', 'string', 'max:255'],
            'plant_name' => ['nullable', 'string', 'max:255'],
            'scheduled_start_at' => ['required', 'date'],
            'scheduled_arrival_at' => ['required', 'date', 'after:scheduled_start_at'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'status' => ['prohibited'],
            'type' => ['prohibited'],
        ];
    }
}

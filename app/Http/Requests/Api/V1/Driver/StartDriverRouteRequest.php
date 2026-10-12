<?php

namespace App\Http\Requests\Api\V1\Driver;

use Illuminate\Foundation\Http\FormRequest;

class StartDriverRouteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'], 'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'accuracy' => ['nullable', 'numeric', 'min:0'], 'location_source' => ['nullable', 'in:gps,network,manual,unknown'],
            'recorded_at' => ['nullable', 'date'], 'connection_status' => ['nullable', 'string', 'max:50'], 'local_event_id' => ['nullable', 'string', 'max:120'],
        ];
    }
}

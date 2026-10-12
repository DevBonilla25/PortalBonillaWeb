<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\DeliveryRouteEventType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDeliveryRouteEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(DeliveryRouteEventType::class)],
            'notes' => ['nullable', 'string', 'max:3000'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'accuracy' => ['nullable', 'numeric', 'min:0'],
            'location_source' => ['nullable', Rule::in(['gps', 'network', 'manual', 'unknown'])],
            'recorded_at' => ['nullable', 'date'],
            'local_event_id' => ['nullable', 'string', 'max:120'],
        ];
    }
}

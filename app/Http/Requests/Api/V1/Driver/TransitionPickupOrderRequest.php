<?php

namespace App\Http\Requests\Api\V1\Driver;

use App\Enums\PickupOrderAction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransitionPickupOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action' => ['required', Rule::enum(PickupOrderAction::class), Rule::notIn(['receive_at_warehouse', 'close', 'cancel'])],
            'notes' => ['nullable', 'string', 'max:1500'],
            'photos' => ['nullable', 'array', 'max:10'], 'photos.*' => ['image', 'max:5120'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'accuracy' => ['nullable', 'numeric', 'min:0'],
            'location_source' => ['nullable', 'in:gps,network,manual,unknown'],
            'occurred_at' => ['nullable', 'date'], 'local_event_id' => ['nullable', 'string', 'max:120'],
        ];
    }
}

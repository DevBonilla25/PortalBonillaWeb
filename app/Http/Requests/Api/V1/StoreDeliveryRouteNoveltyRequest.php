<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreDeliveryRouteNoveltyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'novelty_reason_id' => ['required', 'integer', 'exists:novelty_reasons,id'],
            'description' => ['required', 'string', 'max:1500'],
            'photo' => ['nullable', 'image', 'max:5120'],
            'photos' => ['nullable', 'array', 'max:10'],
            'photos.*' => ['image', 'max:5120'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'accuracy' => ['nullable', 'numeric', 'min:0'],
            'location_source' => ['nullable', 'in:gps,network,manual,unknown'],
            'occurred_at' => ['nullable', 'date'],
        ];
    }
}

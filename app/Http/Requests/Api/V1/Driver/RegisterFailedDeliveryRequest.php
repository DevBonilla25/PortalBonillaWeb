<?php

namespace App\Http\Requests\Api\V1\Driver;

use Illuminate\Foundation\Http\FormRequest;

class RegisterFailedDeliveryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'novelty_reason_id' => ['required', 'integer', 'exists:novelty_reasons,id'],
            'description' => ['required', 'string', 'max:1500'],
            'goods_remain_on_vehicle' => ['required', 'accepted'],
            'return_items' => ['nullable', 'array'],
            'return_items.*.ticket_item_id' => ['required', 'integer', 'distinct', 'exists:ticket_items,id'],
            'return_items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'photo' => ['nullable', 'image', 'max:5120'],
            'photos' => ['nullable', 'array', 'max:10'],
            'photos.*' => ['image', 'max:5120'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0'],
            'occurred_at' => ['nullable', 'date'],
            'connection_status' => ['nullable', 'string', 'max:50'],
            'local_event_id' => ['nullable', 'string', 'max:120'],
        ];
    }
}

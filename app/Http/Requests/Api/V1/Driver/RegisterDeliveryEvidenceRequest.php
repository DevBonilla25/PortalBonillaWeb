<?php

namespace App\Http\Requests\Api\V1\Driver;

use Illuminate\Foundation\Http\FormRequest;

class RegisterDeliveryEvidenceRequest extends FormRequest
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
            'received_by_name' => ['required', 'string', 'max:150'],
            'received_by_identification' => ['nullable', 'string', 'max:50'],
            'photo' => ['required', 'image', 'max:5120'],
            'signature' => ['nullable', 'image', 'max:5120'],
            'observation' => ['nullable', 'string', 'max:1000'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0'],
            'occurred_at' => ['nullable', 'date'],
            'connection_status' => ['nullable', 'string', 'max:50'],
            'local_event_id' => ['nullable', 'string', 'max:120'],
        ];
    }
}

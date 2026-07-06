<?php

namespace App\Http\Requests\Api\V1\Driver;

use Illuminate\Foundation\Http\FormRequest;

class RegisterTicketNoveltyRequest extends FormRequest
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
            'novelty_reason_id' => ['required_without:novelty_type', 'nullable', 'integer', 'exists:novelty_reasons,id'],
            'novelty_type' => ['required_without:novelty_reason_id', 'nullable', 'string', 'max:80'],
            'description' => ['required', 'string', 'max:1500'],
            'photo' => ['nullable', 'image', 'max:5120'],
            'status' => ['nullable', 'string', 'max:50'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0'],
            'occurred_at' => ['nullable', 'date'],
            'connection_status' => ['nullable', 'string', 'max:50'],
            'local_event_id' => ['nullable', 'string', 'max:120'],
        ];
    }
}

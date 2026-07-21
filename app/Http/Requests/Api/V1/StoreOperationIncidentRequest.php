<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOperationIncidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['type' => ['required', Rule::in(['sleep_break', 'food_break', 'plant_delay', 'queue_delay', 'mechanical_issue', 'document_issue', 'plant_no_dispatch', 'accident', 'route_change', 'warehouse_closed', 'other'])], 'description' => ['required', 'string', 'max:1500'], 'latitude' => ['nullable', 'numeric', 'between:-90,90'], 'longitude' => ['nullable', 'numeric', 'between:-180,180']];
    }
}

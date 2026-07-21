<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\LogisticOperationAction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransitionLogisticOperationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['action' => ['required', Rule::enum(LogisticOperationAction::class)], 'notes' => ['nullable', 'string', 'max:3000'], 'latitude' => ['nullable', 'numeric', 'between:-90,90'], 'longitude' => ['nullable', 'numeric', 'between:-180,180']];
    }
}

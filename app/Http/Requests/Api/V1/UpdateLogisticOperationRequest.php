<?php

namespace App\Http\Requests\Api\V1;

class UpdateLogisticOperationRequest extends StoreLogisticOperationRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['origin'][0] = 'sometimes';
        $rules['destination'][0] = 'sometimes';
        $rules['scheduled_arrival_at'][0] = 'sometimes';
        $rules['scheduled_start_at'][0] = 'sometimes';

        return $rules;
    }
}

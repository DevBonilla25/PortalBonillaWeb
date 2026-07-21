<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreOperationIncidentRequest;
use App\Models\LogisticOperation;
use Illuminate\Http\JsonResponse;

class OperationIncidentController extends Controller
{
    public function store(StoreOperationIncidentRequest $request, LogisticOperation $logisticOperation): JsonResponse
    {
        abort_unless((int) $logisticOperation->company_id === (int) $request->user()->company_id, 404);
        if ($request->user()->hasAnyRole(['driver', 'chofer_externo'])) {
            abort_unless((int) $logisticOperation->driver_id === (int) $request->user()->driverProfile?->id, 404);
        }
        $incident = $logisticOperation->incidents()->create($request->safe()->merge(['reported_by' => $request->user()->id])->all());

        return response()->json(['data' => $incident], 201);
    }
}

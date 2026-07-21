<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\LogisticOperationStatus;
use App\Http\Controllers\Controller;
use App\Models\LogisticOperation;
use App\Models\LogisticOperationStop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OperationStopController extends Controller
{
    public function store(Request $request, LogisticOperation $logisticOperation): JsonResponse
    {
        $this->ensureVisible($request, $logisticOperation);
        abort_if(in_array($logisticOperation->status, [LogisticOperationStatus::Completed, LogisticOperationStatus::Cancelled], true), 422, 'No se puede iniciar una parada en una operacion finalizada.');

        $data = $request->validate([
            'reason' => ['required', Rule::in(['sleep', 'food', 'personal', 'mechanical', 'other'])],
            'notes' => ['nullable', 'string', 'max:1500'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $stop = DB::transaction(function () use ($logisticOperation, $request, $data): LogisticOperationStop {
            $operation = LogisticOperation::query()->lockForUpdate()->findOrFail($logisticOperation->id);
            abort_if($operation->stops()->whereNull('finished_at')->exists(), 422, 'Ya existe una parada activa.');

            return $operation->stops()->create([...$data, 'started_by' => $request->user()->id, 'started_at' => now()]);
        });

        return response()->json(['data' => $stop], 201);
    }

    public function finish(Request $request, LogisticOperation $logisticOperation, LogisticOperationStop $stop): JsonResponse
    {
        $this->ensureVisible($request, $logisticOperation);
        abort_unless((int) $stop->logistic_operation_id === (int) $logisticOperation->id, 404);
        abort_if($stop->finished_at !== null, 422, 'La parada ya fue finalizada.');

        $stop->forceFill(['finished_by' => $request->user()->id, 'finished_at' => now()])->save();

        return response()->json(['data' => $stop->refresh()]);
    }

    private function ensureVisible(Request $request, LogisticOperation $operation): void
    {
        abort_unless((int) $operation->company_id === (int) $request->user()->company_id, 404);
        if ($request->user()->hasAnyRole(['driver', 'chofer_externo'])) {
            abort_unless((int) $operation->driver_id === (int) $request->user()->driverProfile?->id, 404);
        }
    }
}

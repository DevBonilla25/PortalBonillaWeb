<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Logistics\TransitionLogisticOperationAction;
use App\Enums\LogisticOperationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreLogisticOperationRequest;
use App\Http\Requests\Api\V1\TransitionLogisticOperationRequest;
use App\Http\Requests\Api\V1\UpdateLogisticOperationRequest;
use App\Http\Resources\Api\V1\LogisticOperationResource;
use App\Models\DriverProfile;
use App\Models\LogisticOperation;
use App\Models\Vehicle;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LogisticOperationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = LogisticOperation::query()->where('company_id', $request->user()->company_id)->latest();
        if ($this->isDriver($request)) {
            $query->where('driver_id', $request->user()->driverProfile?->id ?? 0);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return LogisticOperationResource::collection($query->paginate(min($request->integer('per_page', 15), 50)))->response();
    }

    public function store(StoreLogisticOperationRequest $request): JsonResponse
    {
        abort_if($this->isDriver($request), 403);
        $this->validateResourcesBelongToCompany($request);
        $operation = LogisticOperation::query()->create($request->safe()->merge([
            'company_id' => $request->user()->company_id, 'created_by' => $request->user()->id,
        ])->all());

        return LogisticOperationResource::make($operation)->response()->setStatusCode(201);
    }

    public function show(Request $request, LogisticOperation $logisticOperation): LogisticOperationResource
    {
        $this->ensureVisible($request, $logisticOperation);

        return LogisticOperationResource::make($logisticOperation->load(['incidents', 'mediaAttachments', 'transitions', 'stops']));
    }

    public function update(UpdateLogisticOperationRequest $request, LogisticOperation $logisticOperation): LogisticOperationResource
    {
        $this->ensureVisible($request, $logisticOperation);
        abort_if($this->isDriver($request), 403);
        $this->validateResourcesBelongToCompany($request);
        $logisticOperation->update($request->validated());

        return LogisticOperationResource::make($logisticOperation->refresh());
    }

    public function transition(TransitionLogisticOperationRequest $request, LogisticOperation $logisticOperation, TransitionLogisticOperationAction $transition): JsonResponse
    {
        $this->ensureVisible($request, $logisticOperation);
        try {
            $operation = $transition->execute($logisticOperation, LogisticOperationAction::from($request->validated('action')), $request->user(), $request->safe()->except('action'));
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return LogisticOperationResource::make($operation)->response();
    }

    private function ensureVisible(Request $request, LogisticOperation $operation): void
    {
        abort_unless((int) $operation->company_id === (int) $request->user()->company_id, 404);
        if ($this->isDriver($request)) {
            abort_unless((int) $operation->driver_id === (int) $request->user()->driverProfile?->id, 404);
        }
    }

    private function isDriver(Request $request): bool
    {
        return $request->user()->hasAnyRole(['driver', 'external_driver']);
    }

    private function validateResourcesBelongToCompany(Request $request): void
    {
        if ($request->filled('driver_id')) {
            abort_unless(DriverProfile::query()->whereKey($request->integer('driver_id'))->whereHas('user', fn ($query) => $query->where('company_id', $request->user()->company_id))->exists(), 422, 'El chofer no pertenece a la empresa.');
        }
        if ($request->filled('vehicle_id')) {
            abort_unless(Vehicle::query()->whereKey($request->integer('vehicle_id'))->where('company_id', $request->user()->company_id)->exists(), 422, 'El vehiculo no pertenece a la empresa.');
        }
    }
}

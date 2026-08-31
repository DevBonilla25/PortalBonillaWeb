<?php

namespace App\Actions\Logistics;

use App\Enums\LogisticOperationAction;
use App\Enums\LogisticOperationStatus;
use App\Models\LogisticOperation;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class TransitionLogisticOperationAction
{
    public function execute(LogisticOperation $operation, LogisticOperationAction $action, User $user, array $data = []): LogisticOperation
    {
        return DB::transaction(function () use ($operation, $action, $user, $data): LogisticOperation {
            $operation = LogisticOperation::query()->lockForUpdate()->findOrFail($operation->id);
            [$required, $next, $timestamp] = $this->transitionFor($action);
            $previous = $operation->status;

            if ($action !== LogisticOperationAction::Cancel && $operation->status !== $required) {
                throw new DomainException("La accion {$action->value} no es valida desde el estado {$operation->status->value}.");
            }
            if ($action === LogisticOperationAction::Cancel && in_array($operation->status, [LogisticOperationStatus::Completed, LogisticOperationStatus::Cancelled], true)) {
                throw new DomainException('Una operacion finalizada no se puede cancelar.');
            }
            if ($action === LogisticOperationAction::StartReturn && ! $operation->mediaAttachments()->where('collection', 'plant_exit_document')->exists()) {
                throw new DomainException('Debe adjuntar la guia o documento entregado en planta antes de registrar la salida.');
            }

            $operation->forceFill(array_filter([
                'status' => $next,
                $timestamp => now(),
                'last_transition_by' => $user->id,
                'notes' => $data['notes'] ?? $operation->notes,
            ], fn ($value) => $value !== null))->save();

            if ($action === LogisticOperationAction::FinishUnloading) {
                $operation->forceFill(['finished_at' => $operation->unloading_finished_at])->save();
            }

            $operation->transitions()->create([
                'performed_by' => $user->id,
                'action' => $action->value,
                'from_status' => $previous->value,
                'to_status' => $next->value,
                'notes' => $data['notes'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
            ]);

            return $operation->refresh();
        });
    }

    private function transitionFor(LogisticOperationAction $action): array
    {
        return match ($action) {
            LogisticOperationAction::StartTrip => [LogisticOperationStatus::Planned, LogisticOperationStatus::TravelingToPlant, 'started_at'],
            LogisticOperationAction::ArrivePlant => [LogisticOperationStatus::TravelingToPlant, LogisticOperationStatus::ArrivedAtPlant, 'arrived_plant_at'],
            LogisticOperationAction::StartQueue => [LogisticOperationStatus::ArrivedAtPlant, LogisticOperationStatus::InQueue, 'queue_started_at'],
            LogisticOperationAction::EnterPlant => [LogisticOperationStatus::InQueue, LogisticOperationStatus::EnteredPlant, 'plant_entry_at'],
            LogisticOperationAction::StartLoading => [LogisticOperationStatus::EnteredPlant, LogisticOperationStatus::Loading, 'loading_started_at'],
            LogisticOperationAction::FinishLoading => [LogisticOperationStatus::Loading, LogisticOperationStatus::Loaded, 'loading_finished_at'],
            LogisticOperationAction::StartReturn => [LogisticOperationStatus::Loaded, LogisticOperationStatus::ReturningToOrigin, 'left_plant_at'],
            LogisticOperationAction::ArriveOrigin => [LogisticOperationStatus::ReturningToOrigin, LogisticOperationStatus::ArrivedAtOrigin, 'arrived_origin_at'],
            LogisticOperationAction::StartUnloading => [LogisticOperationStatus::ArrivedAtOrigin, LogisticOperationStatus::Unloading, 'unloading_started_at'],
            LogisticOperationAction::FinishUnloading => [LogisticOperationStatus::Unloading, LogisticOperationStatus::Completed, 'unloading_finished_at'],
            LogisticOperationAction::Cancel => [null, LogisticOperationStatus::Cancelled, 'cancelled_at'],
        };
    }
}

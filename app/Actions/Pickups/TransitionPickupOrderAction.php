<?php

namespace App\Actions\Pickups;

use App\Enums\DeliveryRouteStatus;
use App\Enums\PickupOrderAction;
use App\Enums\PickupOrderStatus;
use App\Models\PickupOrder;
use App\Models\User;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TransitionPickupOrderAction
{
    public function execute(PickupOrder $pickup, PickupOrderAction $action, User $user, array $data = [], array $photos = []): PickupOrder
    {
        return DB::transaction(function () use ($pickup, $action, $user, $data, $photos): PickupOrder {
            $pickup = PickupOrder::query()->lockForUpdate()->findOrFail($pickup->id);
            [$required, $next, $timestamp] = $this->transitionFor($action);

            if (! in_array($action, [PickupOrderAction::ReceiveAtWarehouse, PickupOrderAction::Close, PickupOrderAction::Cancel], true)) {
                $route = $pickup->routeTask()->with('route')->first()?->route;
                if (! $route || ! in_array($route->status, [DeliveryRouteStatus::InProgress, DeliveryRouteStatus::ReturningToWarehouse], true)) {
                    throw new DomainException('Debes iniciar la ruta antes de gestionar esta orden de retiro.');
                }
            }

            if (! in_array($action, [PickupOrderAction::Fail, PickupOrderAction::Cancel], true) && $pickup->status !== $required) {
                throw new DomainException("La accion {$action->value} no es valida desde el estado {$pickup->status->value}.");
            }
            if (in_array($action, [PickupOrderAction::Fail, PickupOrderAction::Cancel], true) && $pickup->status->isFinal()) {
                throw new DomainException('La orden de retiro ya esta finalizada.');
            }
            if (in_array($action, [PickupOrderAction::Fail, PickupOrderAction::Cancel], true) && blank($data['notes'] ?? null)) {
                throw new DomainException('Debe indicar el motivo.');
            }

            $previous = $pickup->status;
            $occurredAt = isset($data['occurred_at']) ? Carbon::parse($data['occurred_at']) : now();
            $pickup->forceFill(array_filter([
                'status' => $next,
                $timestamp => $occurredAt,
                'received_by' => $action === PickupOrderAction::ReceiveAtWarehouse ? $user->id : $pickup->received_by,
                'failure_reason' => $action === PickupOrderAction::Fail ? $data['notes'] : $pickup->failure_reason,
            ], fn ($value) => $value !== null))->save();

            $event = $pickup->events()->create([
                ...$data, 'performed_by' => $user->id, 'action' => $action,
                'from_status' => $previous->value, 'to_status' => $next->value,
                'occurred_at' => $occurredAt, 'received_at' => now(),
            ]);

            $disk = config('filesystems.logistics_media_disk', 'public');
            collect($photos)->filter(fn ($file) => $file instanceof UploadedFile)->each(function (UploadedFile $photo, int $index) use ($pickup, $event, $disk): void {
                $pickup->mediaAttachments()->create([
                    'driver_id' => $pickup->driver_id, 'collection' => "pickup_{$event->action->value}",
                    'disk' => $disk, 'path' => $photo->store("pickup-orders/{$pickup->code}", $disk),
                    'original_name' => $photo->getClientOriginalName(), 'mime_type' => $photo->getMimeType(),
                    'size' => $photo->getSize(), 'sort_order' => $index,
                ]);
            });

            return $pickup->refresh()->load(['events', 'mediaAttachments', 'routeTask.route']);
        });
    }

    private function transitionFor(PickupOrderAction $action): array
    {
        return match ($action) {
            PickupOrderAction::StartTrip => [PickupOrderStatus::Assigned, PickupOrderStatus::EnRoute, 'en_route_at'],
            PickupOrderAction::ArrivePickup => [PickupOrderStatus::EnRoute, PickupOrderStatus::AtPickup, 'arrived_at'],
            PickupOrderAction::StartLoading => [PickupOrderStatus::AtPickup, PickupOrderStatus::Loading, 'loading_at'],
            PickupOrderAction::CompletePickup => [PickupOrderStatus::Loading, PickupOrderStatus::PickedUp, 'picked_up_at'],
            PickupOrderAction::StartWarehouseTransfer => [PickupOrderStatus::PickedUp, PickupOrderStatus::TransportingToWarehouse, 'transporting_at'],
            PickupOrderAction::ReceiveAtWarehouse => [PickupOrderStatus::TransportingToWarehouse, PickupOrderStatus::ReceivedAtWarehouse, 'received_at'],
            PickupOrderAction::Close => [PickupOrderStatus::ReceivedAtWarehouse, PickupOrderStatus::Completed, 'completed_at'],
            PickupOrderAction::Fail => [null, PickupOrderStatus::Failed, 'failed_at'],
            PickupOrderAction::Cancel => [null, PickupOrderStatus::Cancelled, 'cancelled_at'],
        };
    }
}

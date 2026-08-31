<?php

namespace App\Actions\Routes;

use App\Enums\DeliveryRouteEventType;
use App\Enums\DeliveryRouteStatus;
use App\Enums\PickupOrderStatus;
use App\Enums\TicketStatus;
use App\Models\DeliveryRoute;
use App\Models\User;
use DomainException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RecordDeliveryRouteEventAction
{
    public function execute(DeliveryRoute $route, DeliveryRouteEventType $type, User $user, array $data): DeliveryRoute
    {
        return DB::transaction(function () use ($route, $type, $user, $data): DeliveryRoute {
            $route = DeliveryRoute::query()->lockForUpdate()->findOrFail($route->id);
            [$required, $next, $timestamp] = $this->transitionFor($type);
            if ($type !== DeliveryRouteEventType::Cancel && $route->status !== $required) {
                throw new DomainException("El evento {$type->value} no es valido desde el estado {$route->status->value}.");
            }
            if ($type === DeliveryRouteEventType::Cancel && in_array($route->status, [DeliveryRouteStatus::Completed, DeliveryRouteStatus::Cancelled], true)) {
                throw new DomainException('Una ruta finalizada no se puede cancelar.');
            }
            if ($type === DeliveryRouteEventType::Start && ! $route->tasks()->exists()) {
                throw new DomainException('La ruta debe tener al menos una entrega o retiro.');
            }
            if (in_array($type, [DeliveryRouteEventType::StartReturn, DeliveryRouteEventType::ArriveWarehouse], true) && $this->hasUnresolvedOperationalTasks($route)) {
                throw new DomainException('La ruta tiene entregas o retiros pendientes de resolver.');
            }
            if ($type === DeliveryRouteEventType::Complete && $this->hasUnresolvedTicketsForClosure($route)) {
                throw new DomainException('Bodega debe recibir los tickets en retorno antes de finalizar la ruta.');
            }
            if ($type === DeliveryRouteEventType::Complete && $this->hasUnreceivedPickups($route)) {
                throw new DomainException('La ruta tiene retiros pendientes de recibir en bodega.');
            }

            $previous = $route->status;
            $recordedAt = isset($data['recorded_at']) ? Carbon::parse($data['recorded_at']) : now();
            $route->forceFill(['status' => $next, $timestamp => $recordedAt])->save();
            $route->events()->create([...$data, 'performed_by' => $user->id, 'type' => $type, 'from_status' => $previous->value, 'to_status' => $next->value, 'recorded_at' => $recordedAt, 'received_at' => now()]);

            return $route->refresh()->load(['tasks.ticket', 'tasks.pickupOrder', 'events', 'novelties.reason', 'novelties.mediaAttachments']);
        });
    }

    private function transitionFor(DeliveryRouteEventType $type): array
    {
        return match ($type) {
            DeliveryRouteEventType::Start => [DeliveryRouteStatus::Planned, DeliveryRouteStatus::InProgress, 'started_at'],
            DeliveryRouteEventType::StartReturn => [DeliveryRouteStatus::InProgress, DeliveryRouteStatus::ReturningToWarehouse, 'returning_at'],
            DeliveryRouteEventType::ArriveWarehouse => [DeliveryRouteStatus::ReturningToWarehouse, DeliveryRouteStatus::AtWarehouse, 'arrived_warehouse_at'],
            DeliveryRouteEventType::Complete => [DeliveryRouteStatus::AtWarehouse, DeliveryRouteStatus::Completed, 'completed_at'],
            DeliveryRouteEventType::Cancel => [null, DeliveryRouteStatus::Cancelled, 'cancelled_at'],
        };
    }

    private function hasUnresolvedOperationalTasks(DeliveryRoute $route): bool
    {
        $ticketStatuses = [TicketStatus::Delivered, TicketStatus::Returning, TicketStatus::ArrivedBack, TicketStatus::PendingReassignment, TicketStatus::Cancelled];
        $pickupStatuses = collect(PickupOrderStatus::cases())->filter->isRouteResolved()->map->value->all();

        return $this->hasTicketsOutside($route, $ticketStatuses)
            || $route->tasks()->where('task_type', 'pickup')->whereHas('pickupOrder', fn ($query) => $query->whereNotIn('status', $pickupStatuses))->exists();
    }

    private function hasUnresolvedTicketsForClosure(DeliveryRoute $route): bool
    {
        return $this->hasTicketsOutside($route, [TicketStatus::Delivered, TicketStatus::ArrivedBack, TicketStatus::PendingReassignment, TicketStatus::Cancelled]);
    }

    private function hasTicketsOutside(DeliveryRoute $route, array $statuses): bool
    {
        return $route->tasks()->where('task_type', 'delivery')->whereHas('ticket', fn ($query) => $query->whereNotIn('status', array_map(fn (TicketStatus $status) => $status->value, $statuses)))->exists();
    }

    private function hasUnreceivedPickups(DeliveryRoute $route): bool
    {
        $resolved = [PickupOrderStatus::ReceivedAtWarehouse, PickupOrderStatus::Completed, PickupOrderStatus::Failed, PickupOrderStatus::Cancelled];

        return $route->tasks()->where('task_type', 'pickup')->whereHas('pickupOrder', fn ($query) => $query->whereNotIn('status', array_map(fn (PickupOrderStatus $status) => $status->value, $resolved)))->exists();
    }
}

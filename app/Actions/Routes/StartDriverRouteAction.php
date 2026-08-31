<?php

namespace App\Actions\Routes;

use App\Enums\DeliveryRouteEventType;
use App\Enums\DeliveryRouteStatus;
use App\Enums\PickupOrderStatus;
use App\Enums\TicketEventType;
use App\Enums\TicketStatus;
use App\Models\DeliveryRoute;
use App\Models\DriverProfile;
use App\Models\PickupOrder;
use App\Models\Ticket;
use App\Services\TicketEventService;
use App\Services\TicketWorkflowService;
use DomainException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class StartDriverRouteAction
{
    public function __construct(private readonly TicketWorkflowService $workflow, private readonly TicketEventService $events) {}

    public function execute(DriverProfile $driver, array $location = []): DeliveryRoute
    {
        return DB::transaction(function () use ($driver, $location): DeliveryRoute {
            $active = DeliveryRoute::query()->where('driver_id', $driver->id)
                ->whereIn('status', [DeliveryRouteStatus::Planned->value, DeliveryRouteStatus::InProgress->value, DeliveryRouteStatus::ReturningToWarehouse->value, DeliveryRouteStatus::AtWarehouse->value])
                ->lockForUpdate()->latest('id')->first();
            if ($active) {
                return $this->load($active);
            }

            $pendingDispatchCount = Ticket::query()->where('current_driver_id', $driver->id)->whereIn('status', [
                TicketStatus::SentToWarehouse->value,
                TicketStatus::AssignedToWarehouse->value,
                TicketStatus::Picking->value,
                TicketStatus::Loading->value,
                TicketStatus::Loaded->value,
            ])->count();
            if ($pendingDispatchCount > 0) {
                throw new DomainException("Bodega debe despachar {$pendingDispatchCount} ticket(s) antes de iniciar la ruta.");
            }

            $tickets = Ticket::query()->where('current_driver_id', $driver->id)->where('status', TicketStatus::Dispatched)->lockForUpdate()->orderBy('assigned_at')->orderBy('id')->get();
            $pickups = PickupOrder::query()->where('driver_id', $driver->id)->where('status', PickupOrderStatus::Assigned)->whereDoesntHave('routeTask')->lockForUpdate()->orderBy('scheduled_at')->orderBy('id')->get();
            if ($tickets->isEmpty() && $pickups->isEmpty()) {
                throw new DomainException('No tienes entregas despachadas ni retiros asignados para iniciar una ruta.');
            }

            $vehicleIds = $tickets->pluck('current_vehicle_id')->merge($pickups->pluck('vehicle_id'))->filter()->unique()->values();
            if ($vehicleIds->count() !== 1) {
                throw new DomainException('Las actividades asignadas deben pertenecer a un solo vehiculo.');
            }
            $warehouseIds = $tickets->pluck('warehouse_id')->merge($pickups->pluck('warehouse_id'))->filter()->unique()->values();
            if ($warehouseIds->count() > 1) {
                throw new DomainException('Las actividades asignadas pertenecen a diferentes bodegas.');
            }

            $occurredAt = isset($location['recorded_at']) ? Carbon::parse($location['recorded_at']) : now();
            $route = DeliveryRoute::query()->create([
                'company_id' => $driver->user->company_id, 'warehouse_id' => $warehouseIds->first(),
                'driver_id' => $driver->id, 'vehicle_id' => $vehicleIds->first(), 'created_by' => $driver->user_id,
                'status' => DeliveryRouteStatus::InProgress, 'started_at' => $occurredAt,
            ]);

            $sequence = 1;
            foreach ($tickets as $ticket) {
                $previous = $ticket->status;
                $this->workflow->transition($ticket, TicketStatus::InRoute);
                $route->tasks()->create(['sequence' => $sequence++, 'task_type' => 'delivery', 'ticket_id' => $ticket->id]);
                $ticket->deliveryAttempts()->create([
                    'ticket_assignment_id' => $ticket->latestAssignment?->id,
                    'driver_id' => $driver->id,
                    'vehicle_id' => $ticket->current_vehicle_id,
                    'attempt_number' => ((int) $ticket->deliveryAttempts()->max('attempt_number')) + 1,
                    'status' => 'in_progress',
                    'started_at' => $occurredAt,
                ]);
                $this->events->record($ticket, TicketEventType::StatusChanged, $driver->user, $previous, TicketStatus::InRoute, $driver, 'Ruta iniciada por el chofer.', ['delivery_route_id' => $route->id], $location['latitude'] ?? null, $location['longitude'] ?? null, $location['accuracy'] ?? null, $occurredAt, 'mobile', $location['connection_status'] ?? 'online');
            }
            foreach ($pickups as $pickup) {
                $route->tasks()->create(['sequence' => $sequence++, 'task_type' => 'pickup', 'pickup_order_id' => $pickup->id]);
            }

            $route->events()->create([
                ...$location, 'performed_by' => $driver->user_id, 'type' => DeliveryRouteEventType::Start,
                'from_status' => DeliveryRouteStatus::Planned->value, 'to_status' => DeliveryRouteStatus::InProgress->value,
                'recorded_at' => $occurredAt, 'received_at' => now(),
            ]);

            return $this->load($route);
        });
    }

    private function load(DeliveryRoute $route): DeliveryRoute
    {
        return $route->load(['tickets', 'tasks.ticket', 'tasks.pickupOrder.events', 'tasks.pickupOrder.mediaAttachments', 'events', 'novelties.reason', 'novelties.mediaAttachments']);
    }
}

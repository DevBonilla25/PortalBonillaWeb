<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreDeliveryRouteRequest;
use App\Http\Resources\Api\V1\DeliveryRouteResource;
use App\Models\DeliveryRoute;
use App\Models\DriverProfile;
use App\Models\Ticket;
use App\Models\Vehicle;
use App\Models\Warehouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeliveryRouteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = DeliveryRoute::query()->where('company_id', $request->user()->company_id)->with(['tasks.ticket', 'tasks.pickupOrder'])->latest();
        if ($this->isDriver($request)) {
            $query->where('driver_id', $request->user()->driverProfile?->id ?? 0);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return DeliveryRouteResource::collection($query->paginate(min($request->integer('per_page', 15), 50)))->response();
    }

    public function store(StoreDeliveryRouteRequest $request): JsonResponse
    {
        abort_if($this->isDriver($request), 403);
        $data = $request->validated();
        $this->validateAssignment($request, $data);
        $route = DB::transaction(function () use ($request, $data): DeliveryRoute {
            $route = DeliveryRoute::query()->create([...collect($data)->except('ticket_ids')->all(), 'company_id' => $request->user()->company_id, 'created_by' => $request->user()->id]);
            foreach ($data['ticket_ids'] as $index => $ticketId) {
                $route->tickets()->attach($ticketId, ['sequence' => $index + 1]);
                $route->tasks()->create(['sequence' => $index + 1, 'task_type' => 'delivery', 'ticket_id' => $ticketId]);
            }

            return $route;
        });

        return DeliveryRouteResource::make($this->loadRoute($route))->response()->setStatusCode(201);
    }

    public function show(Request $request, DeliveryRoute $deliveryRoute): DeliveryRouteResource
    {
        $this->ensureVisible($request, $deliveryRoute);

        return DeliveryRouteResource::make($this->loadRoute($deliveryRoute));
    }

    private function validateAssignment(Request $request, array $data): void
    {
        $companyId = $request->user()->company_id;
        abort_unless(DriverProfile::query()->whereKey($data['driver_id'])->where('is_active', true)->whereHas('user', fn ($query) => $query->where('company_id', $companyId))->exists(), 422, 'El chofer no pertenece a la empresa o esta inactivo.');
        abort_unless(Vehicle::query()->whereKey($data['vehicle_id'])->where('company_id', $companyId)->where('is_active', true)->exists(), 422, 'El vehiculo no pertenece a la empresa o esta inactivo.');
        if ($data['warehouse_id'] ?? null) {
            abort_unless(Warehouse::query()->whereKey($data['warehouse_id'])->where('company_id', $companyId)->exists(), 422, 'La bodega no pertenece a la empresa.');
        }
        $validTickets = Ticket::query()->whereIn('id', $data['ticket_ids'])->where('company_id', $companyId)->where('current_driver_id', $data['driver_id'])->where('current_vehicle_id', $data['vehicle_id'])->count();
        abort_unless($validTickets === count($data['ticket_ids']), 422, 'Todos los tickets deben estar asignados al chofer y vehiculo de la ruta.');
        $alreadyRouted = DB::table('delivery_route_tasks')->join('delivery_routes', 'delivery_routes.id', '=', 'delivery_route_tasks.delivery_route_id')->whereIn('delivery_route_tasks.ticket_id', $data['ticket_ids'])->whereNotIn('delivery_routes.status', ['completed', 'cancelled'])->exists();
        abort_if($alreadyRouted, 422, 'Uno o mas tickets ya pertenecen a una ruta activa.');
    }

    private function ensureVisible(Request $request, DeliveryRoute $route): void
    {
        abort_unless((int) $route->company_id === (int) $request->user()->company_id, 404);
        if ($this->isDriver($request)) {
            abort_unless((int) $route->driver_id === (int) $request->user()->driverProfile?->id, 404);
        }
    }

    private function isDriver(Request $request): bool
    {
        return $request->user()->hasAnyRole(['driver', 'external_driver']);
    }

    private function loadRoute(DeliveryRoute $route): DeliveryRoute
    {
        return $route->load(['tickets', 'tasks.ticket', 'tasks.pickupOrder.events', 'tasks.pickupOrder.mediaAttachments', 'events', 'novelties.reason', 'novelties.mediaAttachments']);
    }
}

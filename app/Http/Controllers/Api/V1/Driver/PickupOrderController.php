<?php

namespace App\Http\Controllers\Api\V1\Driver;

use App\Actions\Pickups\TransitionPickupOrderAction;
use App\Enums\PickupOrderAction;
use App\Enums\PickupOrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Driver\TransitionPickupOrderRequest;
use App\Http\Resources\Api\V1\PickupOrderResource;
use App\Models\PickupOrder;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PickupOrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $driver = $this->driver($request);

        return PickupOrderResource::collection(PickupOrder::query()
            ->where('driver_id', $driver->id)
            ->whereIn('status', [
                PickupOrderStatus::Assigned->value,
                PickupOrderStatus::EnRoute->value,
                PickupOrderStatus::AtPickup->value,
                PickupOrderStatus::Loading->value,
                PickupOrderStatus::PickedUp->value,
                PickupOrderStatus::TransportingToWarehouse->value,
            ])
            ->with('routeTask.route')
            ->latest()
            ->paginate(20))->response();
    }

    public function show(Request $request, PickupOrder $pickupOrder): PickupOrderResource
    {
        $this->ensureAssigned($request, $pickupOrder);

        return PickupOrderResource::make($pickupOrder->load(['events', 'mediaAttachments', 'routeTask.route']));
    }

    public function storeEvent(TransitionPickupOrderRequest $request, PickupOrder $pickupOrder, TransitionPickupOrderAction $action): JsonResponse
    {
        $this->ensureAssigned($request, $pickupOrder);
        try {
            $pickup = $action->execute($pickupOrder, PickupOrderAction::from($request->validated('action')), $request->user(), $request->safe()->except(['action', 'photos']), $request->file('photos', []));
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return PickupOrderResource::make($pickup)->response();
    }

    private function ensureAssigned(Request $request, PickupOrder $pickup): void
    {
        abort_unless((int) $pickup->driver_id === (int) $this->driver($request)->id, 404);
    }

    private function driver(Request $request)
    {
        $driver = $request->user()->driverProfile;
        abort_unless($driver?->is_active, 403, 'No tienes un perfil de chofer activo.');

        return $driver;
    }
}

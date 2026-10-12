<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Routes\RecordDeliveryRouteEventAction;
use App\Enums\DeliveryRouteEventType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreDeliveryRouteEventRequest;
use App\Http\Resources\Api\V1\DeliveryRouteResource;
use App\Models\DeliveryRoute;
use DomainException;
use Illuminate\Http\JsonResponse;

class DeliveryRouteEventController extends Controller
{
    public function store(StoreDeliveryRouteEventRequest $request, DeliveryRoute $deliveryRoute, RecordDeliveryRouteEventAction $action): JsonResponse
    {
        $driver = $request->user()->driverProfile;
        abort_unless($driver?->is_active && (int) $deliveryRoute->driver_id === (int) $driver->id, 404);
        try {
            $route = $action->execute($deliveryRoute, DeliveryRouteEventType::from($request->validated('type')), $request->user(), $request->safe()->except('type'));
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['data' => ['route' => DeliveryRouteResource::make($route)]], 201);
    }
}

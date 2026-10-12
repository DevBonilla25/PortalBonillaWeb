<?php

namespace App\Http\Controllers\Api\V1\Driver;

use App\Actions\Routes\StartDriverRouteAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Driver\StartDriverRouteRequest;
use App\Http\Resources\Api\V1\DeliveryRouteResource;
use DomainException;
use Illuminate\Http\JsonResponse;

class DriverRouteController extends Controller
{
    public function start(StartDriverRouteRequest $request, StartDriverRouteAction $action): JsonResponse
    {
        $driver = $request->user()->driverProfile;
        abort_unless($driver?->is_active, 403, 'No tienes un perfil de chofer activo.');
        try {
            $route = $action->execute($driver, $request->validated());
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return DeliveryRouteResource::make($route)->response();
    }
}

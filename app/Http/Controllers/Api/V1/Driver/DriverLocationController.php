<?php

namespace App\Http\Controllers\Api\V1\Driver;

use App\Actions\Locations\RecordDriverLocationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Driver\RecordDriverLocationRequest;
use App\Http\Resources\Api\V1\LocationPointResource;
use App\Models\DriverProfile;

class DriverLocationController extends Controller
{
    public function store(RecordDriverLocationRequest $request, RecordDriverLocationAction $action): LocationPointResource
    {
        $point = $action->execute(
            driver: $this->driver($request),
            data: $request->validated(),
        );

        return LocationPointResource::make($point);
    }

    private function driver(RecordDriverLocationRequest $request): DriverProfile
    {
        $driver = $request->user()->driverProfile;

        abort_unless($driver && $driver->is_active, 403, 'No tienes un perfil de chofer activo.');

        return $driver;
    }
}

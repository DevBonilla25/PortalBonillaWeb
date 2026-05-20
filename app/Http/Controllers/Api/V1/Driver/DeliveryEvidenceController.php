<?php

namespace App\Http\Controllers\Api\V1\Driver;

use App\Actions\Deliveries\RegisterDeliveryEvidenceAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Driver\RegisterDeliveryEvidenceRequest;
use App\Http\Resources\Api\V1\DeliveryEvidenceResource;
use App\Models\DriverProfile;
use App\Models\Ticket;

class DeliveryEvidenceController extends Controller
{
    public function store(RegisterDeliveryEvidenceRequest $request, Ticket $ticket, RegisterDeliveryEvidenceAction $action): DeliveryEvidenceResource
    {
        $evidence = $action->execute(
            ticket: $ticket,
            driver: $this->driver($request),
            data: $request->validated(),
            photo: $request->file('photo'),
            signature: $request->file('signature'),
        );

        return DeliveryEvidenceResource::make($evidence);
    }

    private function driver(RegisterDeliveryEvidenceRequest $request): DriverProfile
    {
        $driver = $request->user()->driverProfile;

        abort_unless($driver && $driver->is_active, 403, 'No tienes un perfil de chofer activo.');

        return $driver;
    }
}

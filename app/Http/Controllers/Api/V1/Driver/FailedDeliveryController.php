<?php

namespace App\Http\Controllers\Api\V1\Driver;

use App\Actions\Deliveries\RegisterFailedDeliveryAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Driver\RegisterFailedDeliveryRequest;
use App\Http\Resources\Api\V1\TicketNoveltyResource;
use App\Models\DriverProfile;
use App\Models\Ticket;
use DomainException;
use Illuminate\Http\JsonResponse;

class FailedDeliveryController extends Controller
{
    public function store(RegisterFailedDeliveryRequest $request, Ticket $ticket, RegisterFailedDeliveryAction $action): TicketNoveltyResource|JsonResponse
    {
        try {
            $novelty = $action->execute(
                ticket: $ticket,
                driver: $this->driver($request),
                data: $request->validated(),
                photo: $request->file('photo'),
                photos: $request->file('photos', []),
            );
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return TicketNoveltyResource::make($novelty);
    }

    private function driver(RegisterFailedDeliveryRequest $request): DriverProfile
    {
        $driver = $request->user()->driverProfile;
        abort_unless($driver && $driver->is_active, 403, 'No tienes un perfil de chofer activo.');

        return $driver;
    }
}

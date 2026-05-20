<?php

namespace App\Http\Controllers\Api\V1\Driver;

use App\Actions\Deliveries\ChangeDriverTicketStatusAction;
use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Driver\ChangeTicketStatusRequest;
use App\Http\Resources\Api\V1\DriverTicketResource;
use App\Models\DriverProfile;
use App\Models\Ticket;
use App\Services\DriverTicketQueryService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DriverTicketController extends Controller
{
    public function index(Request $request, DriverTicketQueryService $tickets): JsonResponse
    {
        $driver = $this->driver($request);
        $paginated = $tickets->paginateForDriver(
            driver: $driver,
            perPage: min((int) $request->integer('per_page', 15), 50),
        );

        return DriverTicketResource::collection($paginated)->response();
    }

    public function show(Request $request, Ticket $ticket, DriverTicketQueryService $tickets): DriverTicketResource
    {
        return DriverTicketResource::make($tickets->findForDriver($this->driver($request), $ticket));
    }

    public function changeStatus(ChangeTicketStatusRequest $request, Ticket $ticket, ChangeDriverTicketStatusAction $action): JsonResponse
    {
        try {
            $ticket = $action->execute(
                ticket: $ticket,
                driver: $this->driver($request),
                nextStatus: TicketStatus::from($request->validated('status')),
                data: $request->validated(),
            );
        } catch (DomainException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return DriverTicketResource::make($ticket->load([
            'zone',
            'warehouse',
            'currentVehicle',
            'items',
            'events',
            'deliveryEvidences',
            'novelties',
        ]))->response();
    }

    private function driver(Request $request): DriverProfile
    {
        $driver = $request->user()->driverProfile;

        abort_unless($driver && $driver->is_active, 403, 'No tienes un perfil de chofer activo.');

        return $driver;
    }
}

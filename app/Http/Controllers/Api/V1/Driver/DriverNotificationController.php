<?php

namespace App\Http\Controllers\Api\V1\Driver;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\DriverNotificationResource;
use App\Models\DriverNotification;
use App\Models\DriverProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DriverNotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $driver = $this->driver($request);

        $notifications = $driver->notifications()
            ->latest()
            ->paginate(min((int) $request->integer('per_page', 15), 50));

        return DriverNotificationResource::collection($notifications)->response();
    }

    public function markAsRead(Request $request, DriverNotification $notification): DriverNotificationResource
    {
        $driver = $this->driver($request);

        abort_unless((int) $notification->driver_profile_id === (int) $driver->id, 404);

        if (! $notification->read_at) {
            $notification->forceFill(['read_at' => now()])->save();
        }

        return DriverNotificationResource::make($notification->refresh());
    }

    private function driver(Request $request): DriverProfile
    {
        $driver = $request->user()->driverProfile;

        abort_unless($driver && $driver->is_active, 403, 'No tienes un perfil de chofer activo.');

        return $driver;
    }
}

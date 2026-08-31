<?php

namespace App\Observers;

use App\Actions\Routes\AttachPickupToActiveRouteAction;
use App\Enums\PickupOrderStatus;
use App\Models\DriverProfile;
use App\Models\PickupOrder;
use App\Services\DriverPushNotificationService;
use Illuminate\Validation\ValidationException;

class PickupOrderObserver
{
    public function saving(PickupOrder $pickup): void
    {
        if ($pickup->exists && ! $pickup->isDirty(['company_id', 'driver_id', 'vehicle_id'])) {
            return;
        }

        $driver = DriverProfile::query()->whereKey($pickup->driver_id)
            ->where('is_active', true)
            ->whereHas('user', fn ($query) => $query->where('company_id', $pickup->company_id))
            ->first();

        if (! $driver) {
            throw ValidationException::withMessages(['driver_id' => 'El chofer no pertenece a la empresa o esta inactivo.']);
        }
        if (! $driver->default_vehicle_id) {
            throw ValidationException::withMessages(['vehicle_id' => 'El chofer no tiene un vehiculo predeterminado asignado.']);
        }
        if ((int) $pickup->vehicle_id !== (int) $driver->default_vehicle_id) {
            throw ValidationException::withMessages(['vehicle_id' => 'El vehiculo debe ser el asignado al chofer.']);
        }
    }

    public function created(PickupOrder $pickup): void
    {
        if ($pickup->status !== PickupOrderStatus::Assigned) {
            return;
        }

        $route = app(AttachPickupToActiveRouteAction::class)->execute($pickup);
        app(DriverPushNotificationService::class)->sendPickupAssigned($pickup, $route);
    }

    public function updated(PickupOrder $pickup): void
    {
        if ($pickup->status !== PickupOrderStatus::Assigned || ! $pickup->wasChanged(['warehouse_id', 'driver_id', 'vehicle_id'])) {
            return;
        }

        $route = app(AttachPickupToActiveRouteAction::class)->execute($pickup);
        if ($route) {
            app(DriverPushNotificationService::class)->sendPickupAssigned($pickup, $route);
        }
    }
}

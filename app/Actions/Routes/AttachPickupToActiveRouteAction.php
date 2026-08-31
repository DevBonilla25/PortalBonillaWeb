<?php

namespace App\Actions\Routes;

use App\Enums\DeliveryRouteStatus;
use App\Models\DeliveryRoute;
use App\Models\PickupOrder;
use Illuminate\Support\Facades\DB;

class AttachPickupToActiveRouteAction
{
    public function execute(PickupOrder $pickup): ?DeliveryRoute
    {
        return DB::transaction(function () use ($pickup): ?DeliveryRoute {
            $route = DeliveryRoute::query()->where('driver_id', $pickup->driver_id)->where('vehicle_id', $pickup->vehicle_id)
                ->whereIn('status', [DeliveryRouteStatus::InProgress->value, DeliveryRouteStatus::ReturningToWarehouse->value])
                ->lockForUpdate()->latest('id')->first();
            if (! $route || ($route->warehouse_id && $pickup->warehouse_id && (int) $route->warehouse_id !== (int) $pickup->warehouse_id)) {
                return null;
            }
            if ($pickup->routeTask()->exists()) {
                return $route->load('tasks');
            }
            if (! $route->warehouse_id && $pickup->warehouse_id) {
                $route->forceFill(['warehouse_id' => $pickup->warehouse_id])->save();
            }

            $sequence = ((int) $route->tasks()->max('sequence')) + 1;
            $route->tasks()->create(['sequence' => $sequence, 'task_type' => 'pickup', 'pickup_order_id' => $pickup->id]);

            return $route->refresh()->load('tasks');
        });
    }
}

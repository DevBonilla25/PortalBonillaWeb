<?php

namespace App\Models;

use DomainException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

#[Fillable(['delivery_route_id', 'sequence', 'task_type', 'ticket_id', 'pickup_order_id'])]
class DeliveryRouteTask extends Model
{
    protected function casts(): array
    {
        return ['sequence' => 'integer'];
    }

    protected static function booted(): void
    {
        static::creating(function (DeliveryRouteTask $task): void {
            $task->validateTask();
        });
        static::created(function (DeliveryRouteTask $task): void {
            if ($task->task_type === 'delivery') {
                DB::table('delivery_route_ticket')->updateOrInsert(
                    ['delivery_route_id' => $task->delivery_route_id, 'ticket_id' => $task->ticket_id],
                    ['sequence' => $task->sequence, 'created_at' => now(), 'updated_at' => now()],
                );
            }
        });
        static::deleted(function (DeliveryRouteTask $task): void {
            if ($task->task_type === 'delivery') {
                DB::table('delivery_route_ticket')->where('delivery_route_id', $task->delivery_route_id)->where('ticket_id', $task->ticket_id)->delete();
            }
        });
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(DeliveryRoute::class, 'delivery_route_id');
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function pickupOrder(): BelongsTo
    {
        return $this->belongsTo(PickupOrder::class);
    }

    private function validateTask(): void
    {
        $delivery = $this->task_type === 'delivery' && $this->ticket_id && ! $this->pickup_order_id;
        $pickup = $this->task_type === 'pickup' && $this->pickup_order_id && ! $this->ticket_id;
        if (! $delivery && ! $pickup) {
            throw new DomainException('La actividad debe referenciar exactamente una entrega o un retiro.');
        }

        $route = DeliveryRoute::query()->findOrFail($this->delivery_route_id);
        $task = $delivery ? Ticket::query()->findOrFail($this->ticket_id) : PickupOrder::query()->findOrFail($this->pickup_order_id);
        $driverId = $delivery ? $task->current_driver_id : $task->driver_id;
        $vehicleId = $delivery ? $task->current_vehicle_id : $task->vehicle_id;
        if ((int) $task->company_id !== (int) $route->company_id || (int) $driverId !== (int) $route->driver_id || (int) $vehicleId !== (int) $route->vehicle_id) {
            throw new DomainException('La actividad no pertenece al chofer y vehiculo de la ruta.');
        }
    }
}

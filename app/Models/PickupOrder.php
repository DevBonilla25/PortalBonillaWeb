<?php

namespace App\Models;

use App\Enums\PickupOrderStatus;
use App\Enums\TicketPriority;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable(['company_id', 'warehouse_id', 'driver_id', 'vehicle_id', 'created_by', 'received_by', 'code', 'status', 'priority', 'pickup_name', 'pickup_address', 'pickup_reference', 'contact_name', 'contact_phone', 'google_maps_url', 'item_description', 'scheduled_at', 'en_route_at', 'arrived_at', 'loading_at', 'picked_up_at', 'transporting_at', 'received_at', 'completed_at', 'failed_at', 'cancelled_at', 'failure_reason', 'notes'])]
class PickupOrder extends Model
{
    protected function casts(): array
    {
        return [
            'status' => PickupOrderStatus::class, 'priority' => TicketPriority::class,
            'scheduled_at' => 'datetime', 'en_route_at' => 'datetime', 'arrived_at' => 'datetime',
            'loading_at' => 'datetime', 'picked_up_at' => 'datetime', 'transporting_at' => 'datetime',
            'received_at' => 'datetime', 'completed_at' => 'datetime', 'failed_at' => 'datetime', 'cancelled_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (PickupOrder $pickup): void {
            $pickup->status ??= PickupOrderStatus::Assigned;
            $pickup->priority ??= TicketPriority::Normal;
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(DriverProfile::class, 'driver_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function events(): HasMany
    {
        return $this->hasMany(PickupOrderEvent::class);
    }

    public function routeTask(): HasOne
    {
        return $this->hasOne(DeliveryRouteTask::class);
    }

    public function mediaAttachments(): MorphMany
    {
        return $this->morphMany(MediaAttachment::class, 'attachable');
    }
}

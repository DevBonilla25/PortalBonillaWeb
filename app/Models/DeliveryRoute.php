<?php

namespace App\Models;

use App\Enums\DeliveryRouteStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['company_id', 'warehouse_id', 'driver_id', 'vehicle_id', 'created_by', 'status', 'scheduled_start_at', 'started_at', 'returning_at', 'arrived_warehouse_at', 'completed_at', 'cancelled_at', 'notes'])]
class DeliveryRoute extends Model
{
    protected function casts(): array
    {
        return ['status' => DeliveryRouteStatus::class, 'scheduled_start_at' => 'datetime', 'started_at' => 'datetime', 'returning_at' => 'datetime', 'arrived_warehouse_at' => 'datetime', 'completed_at' => 'datetime', 'cancelled_at' => 'datetime'];
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

    public function tickets(): BelongsToMany
    {
        return $this->belongsToMany(Ticket::class, 'delivery_route_ticket')->withPivot('sequence')->withTimestamps()->orderByPivot('sequence');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(DeliveryRouteTask::class)->orderBy('sequence');
    }

    public function events(): HasMany
    {
        return $this->hasMany(DeliveryRouteEvent::class);
    }

    public function novelties(): HasMany
    {
        return $this->hasMany(DeliveryRouteNovelty::class);
    }
}

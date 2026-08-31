<?php

namespace App\Models;

use App\Enums\LogisticOperationStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable(['company_id', 'driver_id', 'vehicle_id', 'created_by', 'last_transition_by', 'type', 'status', 'origin', 'destination', 'plant_name', 'scheduled_start_at', 'scheduled_arrival_at', 'started_at', 'arrived_plant_at', 'queue_started_at', 'plant_entry_at', 'loading_started_at', 'loading_finished_at', 'left_plant_at', 'arrived_origin_at', 'unloading_started_at', 'unloading_finished_at', 'finished_at', 'cancelled_at', 'notes'])]
class LogisticOperation extends Model
{
    protected function casts(): array
    {
        return ['status' => LogisticOperationStatus::class, 'scheduled_start_at' => 'datetime', 'scheduled_arrival_at' => 'datetime', 'started_at' => 'datetime', 'arrived_plant_at' => 'datetime', 'queue_started_at' => 'datetime', 'plant_entry_at' => 'datetime', 'loading_started_at' => 'datetime', 'loading_finished_at' => 'datetime', 'left_plant_at' => 'datetime', 'arrived_origin_at' => 'datetime', 'unloading_started_at' => 'datetime', 'unloading_finished_at' => 'datetime', 'finished_at' => 'datetime', 'cancelled_at' => 'datetime'];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(DriverProfile::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lastTransitionBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_transition_by');
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(OperationIncident::class);
    }

    public function transitions(): HasMany
    {
        return $this->hasMany(LogisticOperationTransition::class);
    }

    public function stops(): HasMany
    {
        return $this->hasMany(LogisticOperationStop::class);
    }

    public function activeStop(): HasMany
    {
        return $this->hasMany(LogisticOperationStop::class)->whereNull('finished_at');
    }

    public function mediaAttachments(): MorphMany
    {
        return $this->morphMany(MediaAttachment::class, 'attachable');
    }
}

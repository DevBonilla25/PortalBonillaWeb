<?php

namespace App\Models;

use App\Enums\DriverStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'employee_id',
    'default_vehicle_id',
    'license_number',
    'license_type',
    'license_expires_at',
    'status',
    'last_latitude',
    'last_longitude',
    'last_location_at',
    'last_connection_at',
    'is_active',
    'observations',
])]
class DriverProfile extends Model
{
    protected function casts(): array
    {
        return [
            'license_expires_at' => 'date',
            'status' => DriverStatus::class,
            'last_latitude' => 'decimal:7',
            'last_longitude' => 'decimal:7',
            'last_location_at' => 'datetime',
            'last_connection_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function defaultVehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'default_vehicle_id');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'current_driver_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(TicketAssignment::class, 'driver_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(TicketEvent::class, 'driver_id');
    }

    public function deliveryEvidences(): HasMany
    {
        return $this->hasMany(DeliveryEvidence::class, 'driver_id');
    }

    public function novelties(): HasMany
    {
        return $this->hasMany(TicketNovelty::class, 'driver_id');
    }

    public function locationPoints(): HasMany
    {
        return $this->hasMany(LocationPoint::class, 'driver_id');
    }

    public function fcmTokens(): HasMany
    {
        return $this->hasMany(DriverFcmToken::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(DriverNotification::class);
    }
}

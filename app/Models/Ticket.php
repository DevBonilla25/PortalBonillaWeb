<?php

namespace App\Models;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'company_id',
    'branch_id',
    'warehouse_id',
    'contact_id',
    'zone_id',
    'cashier_id',
    'current_driver_id',
    'current_vehicle_id',
    'assigned_by',
    'ticket_code',
    'guide_number',
    'source_image_path',
    'customer_name',
    'customer_phone',
    'delivery_address',
    'delivery_reference',
    'priority',
    'status',
    'assigned_at',
    'dispatched_at',
    'delivered_at',
    'returned_at',
    'closed_at',
    'observations',
])]
class Ticket extends Model
{
    protected static function booted(): void
    {
        static::creating(function (Ticket $ticket): void {
            $ticket->priority ??= TicketPriority::Normal;
            $ticket->status ??= TicketStatus::Created;
        });
    }

    protected function casts(): array
    {
        return [
            'priority' => TicketPriority::class,
            'status' => TicketStatus::class,
            'assigned_at' => 'datetime',
            'dispatched_at' => 'datetime',
            'delivered_at' => 'datetime',
            'returned_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function currentDriver(): BelongsTo
    {
        return $this->belongsTo(DriverProfile::class, 'current_driver_id');
    }

    public function currentVehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'current_vehicle_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TicketItem::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(TicketAssignment::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(TicketEvent::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(TicketDocument::class);
    }
}

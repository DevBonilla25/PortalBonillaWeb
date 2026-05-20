<?php

namespace App\Models;

use App\Enums\TicketAssignmentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
    'ticket_id',
    'driver_id',
    'vehicle_id',
    'warehouse_user_id',
    'assigned_by',
    'assigned_at',
    'status',
    'internal_observation',
])]
class TicketAssignment extends Model
{
    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'status' => TicketAssignmentStatus::class,
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(DriverProfile::class, 'driver_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function warehouseUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'warehouse_user_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function assistants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'ticket_assignment_assistants', 'ticket_assignment_id', 'assistant_user_id')
            ->withTimestamps();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'ticket_id',
    'ticket_assignment_id',
    'driver_id',
    'vehicle_id',
    'failure_novelty_id',
    'attempt_number',
    'status',
    'return_items',
    'goods_remain_on_vehicle',
    'started_at',
    'completed_at',
    'warehouse_received_at',
    'warehouse_received_by',
    'warehouse_observation',
])]
class DeliveryAttempt extends Model
{
    protected function casts(): array
    {
        return [
            'return_items' => 'array',
            'goods_remain_on_vehicle' => 'boolean',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'warehouse_received_at' => 'datetime',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(TicketAssignment::class, 'ticket_assignment_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(DriverProfile::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function failureNovelty(): BelongsTo
    {
        return $this->belongsTo(TicketNovelty::class, 'failure_novelty_id');
    }

    public function warehouseReceiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'warehouse_received_by');
    }
}

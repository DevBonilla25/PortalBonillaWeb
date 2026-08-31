<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['logistic_operation_id', 'reported_by', 'type', 'description', 'latitude', 'longitude'])]
class OperationIncident extends Model
{
    protected function casts(): array
    {
        return ['latitude' => 'decimal:7', 'longitude' => 'decimal:7'];
    }

    public function operation(): BelongsTo
    {
        return $this->belongsTo(LogisticOperation::class, 'logistic_operation_id');
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['logistic_operation_id', 'started_by', 'finished_by', 'reason', 'notes', 'latitude', 'longitude', 'started_at', 'finished_at'])]
class LogisticOperationStop extends Model
{
    protected function casts(): array
    {
        return ['latitude' => 'decimal:7', 'longitude' => 'decimal:7', 'started_at' => 'datetime', 'finished_at' => 'datetime'];
    }

    public function operation(): BelongsTo
    {
        return $this->belongsTo(LogisticOperation::class, 'logistic_operation_id');
    }

    public function starter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by');
    }

    public function finisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finished_by');
    }
}

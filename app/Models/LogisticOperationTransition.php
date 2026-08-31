<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['logistic_operation_id', 'performed_by', 'action', 'from_status', 'to_status', 'notes', 'latitude', 'longitude'])]
class LogisticOperationTransition extends Model
{
    protected function casts(): array
    {
        return ['latitude' => 'decimal:7', 'longitude' => 'decimal:7'];
    }

    public function operation(): BelongsTo
    {
        return $this->belongsTo(LogisticOperation::class, 'logistic_operation_id');
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable(['delivery_route_id', 'novelty_reason_id', 'reported_by', 'description', 'status', 'latitude', 'longitude', 'accuracy', 'location_source', 'occurred_at'])]
class DeliveryRouteNovelty extends Model
{
    protected function casts(): array
    {
        return ['latitude' => 'decimal:7', 'longitude' => 'decimal:7', 'accuracy' => 'decimal:2', 'occurred_at' => 'datetime'];
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(DeliveryRoute::class, 'delivery_route_id');
    }

    public function reason(): BelongsTo
    {
        return $this->belongsTo(NoveltyReason::class, 'novelty_reason_id');
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function mediaAttachments(): MorphMany
    {
        return $this->morphMany(MediaAttachment::class, 'attachable');
    }
}

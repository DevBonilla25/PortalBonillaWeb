<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'company_id',
    'code',
    'name',
    'description',
    'requires_photo',
    'is_active',
    'sort_order',
])]
class NoveltyReason extends Model
{
    protected function casts(): array
    {
        return [
            'requires_photo' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @param  Builder<NoveltyReason>  $query
     * @return Builder<NoveltyReason>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function novelties(): HasMany
    {
        return $this->hasMany(TicketNovelty::class);
    }
}

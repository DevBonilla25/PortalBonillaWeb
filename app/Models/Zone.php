<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'company_id',
    'code',
    'name',
    'description',
    'color',
    'is_active',
])]
class Zone extends Model
{
    private const DEFAULT_COLORS = [
        '#2563EB',
        '#059669',
        '#DC2626',
        '#9333EA',
        '#0891B2',
        '#CA8A04',
        '#DB2777',
        '#16A34A',
        '#7C3AED',
        '#EA580C',
    ];

    protected static function booted(): void
    {
        static::creating(function (Zone $zone): void {
            $zone->color ??= self::colorFor($zone->code ?: $zone->name);
        });
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public static function colorFor(?string $seed): string
    {
        $seed = $seed ?: (string) random_int(1, PHP_INT_MAX);

        return self::DEFAULT_COLORS[abs(crc32($seed)) % count(self::DEFAULT_COLORS)];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }
}

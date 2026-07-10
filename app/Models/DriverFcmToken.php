<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'driver_profile_id',
    'user_id',
    'token',
    'token_hash',
    'platform',
    'last_seen_at',
])]
class DriverFcmToken extends Model
{
    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
        ];
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(DriverProfile::class, 'driver_profile_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

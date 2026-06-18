<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

#[Fillable([
    'company_id',
    'employee_id',
    'morfeus_user_id',
    'name',
    'email',
    'password',
    'phone',
    'is_active',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function driverProfile(): HasOne
    {
        return $this->hasOne(DriverProfile::class);
    }

    public function cashierTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'cashier_id');
    }

    public function assignedTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_by');
    }

    public function ticketEvents(): HasMany
    {
        return $this->hasMany(TicketEvent::class);
    }

    public function uploadedTicketDocuments(): HasMany
    {
        return $this->hasMany(TicketDocument::class, 'uploaded_by');
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active
            && $panel->getId() === 'admin'
            && $this->hasAnyRole([
                'super_admin',
                'admin',
                'cashier',
                'vendedor',
                'warehouse_operator',
                'warehouse_assistant',
                'jefe_bodega',
                'auxiliar_bodega',
                'driver',
                'chofer',
            ]);
    }
}

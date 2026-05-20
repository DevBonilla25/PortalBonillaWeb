<?php

namespace App\Models;

use App\Enums\ContactType;
use App\Enums\IdentificationType;
use App\Enums\PersonType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'company_id',
    'contact_type',
    'person_type',
    'identification_type',
    'identification_number',
    'first_name',
    'last_name',
    'business_name',
    'commercial_name',
    'email',
    'phone',
    'secondary_phone',
    'address',
    'city',
    'province',
    'country',
    'is_customer',
    'is_supplier',
    'is_employee_related',
    'is_active',
])]
class Contact extends Model
{
    protected $appends = ['display_name'];

    protected function casts(): array
    {
        return [
            'contact_type' => ContactType::class,
            'person_type' => PersonType::class,
            'identification_type' => IdentificationType::class,
            'is_customer' => 'boolean',
            'is_supplier' => 'boolean',
            'is_employee_related' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function getDisplayNameAttribute(): string
    {
        if ($this->contact_type === ContactType::Company) {
            return $this->business_name
                ?? $this->commercial_name
                ?? '—';
        }

        $name = trim("{$this->first_name} {$this->last_name}");

        return $name !== '' ? $name : '—';
    }
}

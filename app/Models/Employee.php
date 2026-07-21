<?php

namespace App\Models;

use App\Enums\EmploymentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'company_id',
    'contact_id',
    'employee_code',
    'first_name',
    'last_name',
    'identification_number',
    'phone',
    'email',
    'branch_id',
    'warehouse_id',
    'job_position',
    'employment_status',
    'hire_date',
    'termination_date',
    'is_active',
])]
class Employee extends Model
{
    protected $appends = ['display_name'];

    protected function casts(): array
    {
        return [
            'employment_status' => EmploymentStatus::class,
            'hire_date' => 'date',
            'termination_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function driverProfile(): HasOne
    {
        return $this->hasOne(DriverProfile::class);
    }

    public function getDisplayNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }
}

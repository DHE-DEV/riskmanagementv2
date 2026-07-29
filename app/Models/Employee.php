<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Employee extends Model
{
    protected $fillable = [
        'customer_id',
        'branch_id',
        'salutation',
        'title',
        'first_name',
        'last_name',
        'email',
        'phone',
        'mobile',
        'position',
        'department',
        'department_id',
        'personnel_number',
        'provider',
        'provider_id',
        'is_active',
        'active_from',
        'active_until',
        'notes',
        'legacy_usersweb_id',
        'legacy_client_account_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'active_from' => 'date',
        'active_until' => 'date',
    ];

    protected $appends = ['is_currently_active'];

    public function getIsCurrentlyActiveAttribute(): bool
    {
        return $this->isCurrentlyActive();
    }

    /**
     * Check if the employee is currently active, considering date range.
     */
    public function isCurrentlyActive(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $today = now()->startOfDay();

        if ($this->active_from && $today->lt($this->active_from)) {
            return false;
        }

        if ($this->active_until && $today->gt($this->active_until)) {
            return false;
        }

        return true;
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function departmentRelation(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(EmployeeGroup::class);
    }
}

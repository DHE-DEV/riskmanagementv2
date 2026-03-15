<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhoneNumber extends Model
{
    protected $fillable = ['customer_id', 'branch_id', 'label', 'number', 'type', 'is_primary', 'notes', 'department_id', 'sort_order'];

    protected $casts = ['is_primary' => 'boolean'];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}

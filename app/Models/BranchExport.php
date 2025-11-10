<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchExport extends Model
{
    protected $fillable = [
        'customer_id',
        'filename',
        'count',
        'status',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}

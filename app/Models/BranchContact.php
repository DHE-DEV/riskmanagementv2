<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchContact extends Model
{
    protected $fillable = [
        'branch_id', 'salutation', 'title', 'first_name', 'last_name',
        'function', 'department', 'phone', 'mobile', 'fax', 'email',
        'notes', 'sort_order',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Module extends Model
{
    protected $fillable = [
        'product_version_id',
        'name',
        'subtitle',
        'description',
        'internal_description',
        'price_monthly',
        'price_yearly',
        'price_one_time',
        'price_setup',
        'currency',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price_monthly' => 'decimal:2',
        'price_yearly' => 'decimal:2',
        'price_one_time' => 'decimal:2',
        'price_setup' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function productVersion(): BelongsTo
    {
        return $this->belongsTo(ProductVersion::class);
    }
}

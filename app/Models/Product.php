<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'name',
        'subtitle',
        'description',
        'internal_description',
        'price_monthly',
        'price_yearly',
        'price_one_time',
        'currency',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price_monthly' => 'decimal:2',
        'price_yearly' => 'decimal:2',
        'price_one_time' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function versions(): HasMany
    {
        return $this->hasMany(ProductVersion::class)->orderBy('sort_order');
    }
}

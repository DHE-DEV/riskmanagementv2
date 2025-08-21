<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SocialLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'platform', 'title', 'url', 'description', 'latitude', 'longitude', 'country', 'city', 'tags', 'is_active'
    ];

    protected $casts = [
        'latitude' => 'decimal:6',
        'longitude' => 'decimal:6',
        'tags' => 'array',
        'is_active' => 'boolean',
    ];
}



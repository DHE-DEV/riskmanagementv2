<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TourismCruiseLine extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tourism_cruise_lines';

    protected $fillable = [
        'code',
        'name',
        'public_name',
        'data_customization_account_id',
        'data_customization_restricted_access',
        'use_default_content',
    ];

    protected $casts = [
        'data_customization_restricted_access' => 'boolean',
        'use_default_content' => 'integer',
    ];

    /**
     * Get the ships for this cruise line.
     */
    public function ships()
    {
        return $this->hasMany(TourismCruiseShip::class, 'line_id');
    }

    /**
     * Get the display name (prefer public_name over name)
     */
    public function getDisplayNameAttribute()
    {
        return $this->public_name ?: $this->name;
    }
}

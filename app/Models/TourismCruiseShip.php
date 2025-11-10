<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TourismCruiseShip extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tourism_cruise_ships';

    protected $fillable = [
        'line_id',
        'cruise_compass_id',
        'name',
    ];

    protected $casts = [
        'line_id' => 'integer',
        'cruise_compass_id' => 'integer',
    ];

    /**
     * Get the cruise line that owns the ship.
     */
    public function cruiseLine()
    {
        return $this->belongsTo(TourismCruiseLine::class, 'line_id');
    }

    /**
     * Get the routes for this ship.
     */
    public function routes()
    {
        return $this->hasMany(TourismCruiseRoute::class, 'ship_id');
    }
}

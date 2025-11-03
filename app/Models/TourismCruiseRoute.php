<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TourismCruiseRoute extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tourism_cruise_routes';

    protected $fillable = [
        'ship_id',
        'name',
    ];

    protected $casts = [
        'ship_id' => 'integer',
    ];

    /**
     * Get the ship that owns the route.
     */
    public function ship()
    {
        return $this->belongsTo(TourismCruiseShip::class, 'ship_id');
    }

    /**
     * Get the cruises (dates) for this route.
     */
    public function cruises()
    {
        return $this->hasMany(TourismCruiseRouteCruise::class, 'cruise_compass_route_id', 'id');
    }

    /**
     * Get the course (ports) for this route.
     */
    public function courses()
    {
        return $this->hasMany(TourismCruiseRouteCourse::class, 'cruise_compass_route_id', 'id')
            ->orderBy('day');
    }

    /**
     * Get the ports visited on this route through courses.
     */
    public function ports()
    {
        return $this->hasManyThrough(
            TourismCruisePort::class,
            TourismCruiseRouteCourse::class,
            'cruise_compass_route_id', // Foreign key on courses table
            'id', // Foreign key on ports table
            'id', // Local key on routes table
            'port_id' // Local key on courses table
        );
    }
}

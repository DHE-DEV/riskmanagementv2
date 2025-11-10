<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TourismCruiseRouteCourse extends Model
{
    use HasFactory;

    protected $table = 'tourism_cruise_route_courses';

    protected $fillable = [
        'cruise_compass_route_id',
        'day',
        'port_id',
        'arrive_at',
        'depart_at',
    ];

    protected $casts = [
        'cruise_compass_route_id' => 'integer',
        'day' => 'integer',
        'port_id' => 'integer',
    ];

    /**
     * Get the route that owns the course.
     */
    public function route()
    {
        return $this->belongsTo(TourismCruiseRoute::class, 'cruise_compass_route_id');
    }

    /**
     * Get the port for this course.
     */
    public function port()
    {
        return $this->belongsTo(TourismCruisePort::class, 'port_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TourismCruiseRouteCruise extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tourism_cruise_route_cruises';

    protected $fillable = [
        'cruise_compass_id',
        'cruise_compass_route_id',
        'start_date',
        'duration_in_days',
    ];

    protected $casts = [
        'start_date' => 'date',
        'duration_in_days' => 'integer',
        'cruise_compass_route_id' => 'integer',
    ];

    /**
     * Get the route that owns the cruise.
     */
    public function route()
    {
        return $this->belongsTo(TourismCruiseRoute::class, 'cruise_compass_route_id');
    }

    /**
     * Get the end date based on start date and duration.
     */
    public function getEndDateAttribute()
    {
        return $this->start_date?->addDays($this->duration_in_days);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TourismCruisePort extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tourism_cruise_ports';

    protected $fillable = [
        'country_id',
        'code',
        'name',
        'geocode_lat',
        'geocode_lng',
    ];

    protected $casts = [
        'country_id' => 'integer',
        'geocode_lat' => 'decimal:7',
        'geocode_lng' => 'decimal:7',
    ];

    /**
     * Get the country that the port belongs to.
     */
    public function country()
    {
        return $this->belongsTo(Country::class);
    }
}

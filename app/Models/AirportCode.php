<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AirportCode extends Model
{
    protected $table = 'airport_codes_1';

    protected $fillable = [
        'id',
        'ident',
        'type',
        'name',
        'latitude_deg',
        'longitude_deg',
        'elevation_ft',
        'continent',
        'iso_country',
        'iso_region',
        'municipality',
        'scheduled_service',
        'icao_code',
        'iata_code',
        'gps_code',
        'local_code',
        'home_link',
        'wikipedia_link',
        'keywords',
    ];

    protected $casts = [
        'latitude_deg' => 'decimal:8',
        'longitude_deg' => 'decimal:8',
        'elevation_ft' => 'integer',
    ];

    public $incrementing = false;
}

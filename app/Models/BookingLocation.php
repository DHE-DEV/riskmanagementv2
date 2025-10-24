<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingLocation extends Model
{
    protected $fillable = [
        'type',
        'name',
        'description',
        'url',
        'address',
        'postal_code',
        'city',
        'latitude',
        'longitude',
        'phone',
        'email',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    /**
     * Scope für Online-Buchungen
     */
    public function scopeOnline($query)
    {
        return $query->where('type', 'online');
    }

    /**
     * Scope für stationäre Standorte
     */
    public function scopeStationary($query)
    {
        return $query->where('type', 'stationary');
    }

    /**
     * Scope für Umkreissuche (Haversine-Formel)
     */
    public function scopeWithinRadius($query, $latitude, $longitude, $radiusKm)
    {
        $earthRadiusKm = 6371;

        return $query->selectRaw(
            "*, (
                {$earthRadiusKm} * acos(
                    cos(radians(?)) * cos(radians(latitude)) *
                    cos(radians(longitude) - radians(?)) +
                    sin(radians(?)) * sin(radians(latitude))
                )
            ) AS distance",
            [$latitude, $longitude, $latitude]
        )
        ->having('distance', '<=', $radiusKm)
        ->orderBy('distance');
    }
}

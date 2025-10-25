<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingLocation extends Model
{
    protected $fillable = [
        'customer_id',
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

    /**
     * Scope für aktive Directory Listings
     * Filtert nur Locations von Kunden, die directory_listing_active = true haben
     */
    public function scopeActiveListings($query)
    {
        return $query->whereHas('customer', function ($q) {
            $q->where('directory_listing_active', true);
        });
    }

    /**
     * Beziehung zum Customer
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}

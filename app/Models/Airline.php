<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Airline extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'iata_code',
        'icao_code',
        'home_country_id',
        'headquarters',
        'website',
        'booking_url',
        'contact_info',
        'baggage_rules',
        'cabin_classes',
        'pet_policy',
        'lounges',
        'is_active',
    ];

    protected $casts = [
        'contact_info' => 'array',
        'baggage_rules' => 'array',
        'cabin_classes' => 'array',
        'pet_policy' => 'array',
        'lounges' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the home country for this airline.
     */
    public function homeCountry(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'home_country_id');
    }

    /**
     * Get the airports this airline flies to/from.
     */
    public function airports(): BelongsToMany
    {
        return $this->belongsToMany(Airport::class, 'airline_airport')
            ->withPivot('direction', 'terminal')
            ->withTimestamps();
    }

    /**
     * Get available cabin class options.
     */
    public static function getCabinClassOptions(): array
    {
        return [
            'economy' => 'Economy',
            'premium_economy' => 'Premium Economy',
            'business' => 'Business Class',
            'first' => 'First Class',
        ];
    }
}

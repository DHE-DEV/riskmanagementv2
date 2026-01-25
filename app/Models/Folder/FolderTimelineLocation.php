<?php

namespace App\Models\Folder;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class FolderTimelineLocation extends BaseCustomerModel
{
    protected $table = 'folder_timeline_locations';

    public $timestamps = false;

    protected $fillable = [
        'folder_id',
        'itinerary_id',
        'customer_id',
        'location_type',
        'source_type',
        'source_id',
        'lat',
        'lng',
        'location_code',
        'location_name',
        'country_code',
        'start_time',
        'end_time',
        'participant_ids',
        'participant_nationalities',
    ];

    protected $casts = [
        'lat' => 'decimal:8',
        'lng' => 'decimal:8',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'participant_ids' => 'array',
        'participant_nationalities' => 'array',
    ];

    /**
     * Get the folder that owns the timeline location.
     */
    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class);
    }

    /**
     * Get the itinerary that owns the timeline location.
     */
    public function itinerary(): BelongsTo
    {
        return $this->belongsTo(FolderItinerary::class, 'itinerary_id');
    }

    /**
     * Get the customer.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Scope to find locations within a radius (in kilometers).
     */
    public function scopeWithinRadius(Builder $query, float $lat, float $lng, float $radiusKm): Builder
    {
        $radiusMeters = $radiusKm * 1000;

        return $query->whereRaw(
            "ST_Distance_Sphere(point, ST_GeomFromText('POINT(? ?)', 4326)) <= ?",
            [$lng, $lat, $radiusMeters]
        );
    }

    /**
     * Scope to calculate distance from a point.
     */
    public function scopeWithDistance(Builder $query, float $lat, float $lng): Builder
    {
        return $query->selectRaw(
            "*, (ST_Distance_Sphere(point, ST_GeomFromText('POINT(? ?)', 4326)) / 1000) as distance_km",
            [$lng, $lat]
        );
    }

    /**
     * Scope to find locations active during a time range.
     */
    public function scopeActiveDuring(Builder $query, string $startTime, string $endTime): Builder
    {
        return $query->where(function ($q) use ($startTime, $endTime) {
            $q->whereBetween('start_time', [$startTime, $endTime])
                ->orWhereBetween('end_time', [$startTime, $endTime])
                ->orWhere(function ($q2) use ($startTime, $endTime) {
                    $q2->where('start_time', '<=', $startTime)
                        ->where('end_time', '>=', $endTime);
                });
        });
    }

    /**
     * Scope to filter by country code.
     */
    public function scopeInCountry(Builder $query, string $countryCode): Builder
    {
        return $query->where('country_code', $countryCode);
    }

    /**
     * Scope to filter by location type.
     */
    public function scopeOfType(Builder $query, string|array $types): Builder
    {
        if (is_array($types)) {
            return $query->whereIn('location_type', $types);
        }

        return $query->where('location_type', $types);
    }

    /**
     * Scope to filter by participant nationality.
     */
    public function scopeWithNationality(Builder $query, string|array $nationalities): Builder
    {
        if (is_array($nationalities)) {
            return $query->where(function ($q) use ($nationalities) {
                foreach ($nationalities as $nationality) {
                    $q->orWhereJsonContains('participant_nationalities', $nationality);
                }
            });
        }

        return $query->whereJsonContains('participant_nationalities', $nationalities);
    }

    /**
     * Update spatial point when coordinates change.
     * Note: SRID 4326 (WGS 84) expects POINT(latitude longitude) axis order
     */
    protected static function booted(): void
    {
        parent::booted();

        static::creating(function ($model) {
            if ($model->lat && $model->lng) {
                $model->setAttribute('point', DB::raw("ST_GeomFromText('POINT({$model->lat} {$model->lng})', 4326)"));
            } else {
                // Set point to NULL if no coordinates
                $model->setAttribute('point', null);
            }
        });

        static::updating(function ($model) {
            if ($model->isDirty(['lat', 'lng'])) {
                if ($model->lat && $model->lng) {
                    $model->setAttribute('point', DB::raw("ST_GeomFromText('POINT({$model->lat} {$model->lng})', 4326)"));
                } else {
                    // Set point to NULL if coordinates are removed
                    $model->setAttribute('point', null);
                }
            }
        });
    }
}

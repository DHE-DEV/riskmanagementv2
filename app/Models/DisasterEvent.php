<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DisasterEvent extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'severity',
        'event_type',
        'event_type_id',
        'lat',
        'lng',
        'radius_km',
        'country_id',
        'region_id',
        'city_id',
        'affected_areas',
        'event_date',
        'start_time',
        'end_time',
        'is_active',
        'impact_assessment',
        'travel_recommendations',
        'official_sources',
        'media_coverage',
        'tourism_impact',
        'external_sources',
        'last_updated',
        'confidence_score',
        'processing_status',
        'ai_summary',
        'ai_recommendations',
        'crisis_communication',
        'keywords',
        'magnitude',
        'casualties',
        'economic_impact',
        'infrastructure_damage',
        'emergency_response',
        'recovery_status',
        'external_id',
        'gdacs_event_id',
        'gdacs_episode_id',
        'gdacs_alert_level',
        'gdacs_alert_score',
        'gdacs_episode_alert_level',
        'gdacs_episode_alert_score',
        'gdacs_event_name',
        'gdacs_calculation_type',
        'gdacs_severity_value',
        'gdacs_severity_unit',
        'gdacs_severity_text',
        'gdacs_population_value',
        'gdacs_population_unit',
        'gdacs_population_text',
        'gdacs_vulnerability',
        'gdacs_iso3',
        'gdacs_country',
        'gdacs_glide',
        'gdacs_bbox',
        'gdacs_cap_url',
        'gdacs_icon_url',
        'gdacs_version',
        'gdacs_temporary',
        'gdacs_is_current',
        'gdacs_duration_weeks',
        'gdacs_resources',
        'gdacs_map_image',
        'gdacs_map_link',
        'gdacs_date_added',
        'gdacs_date_modified',
        'weather_conditions',
        'evacuation_info',
        'transportation_impact',
        'accommodation_impact',
        'communication_status',
        'health_services_status',
        'utility_services_status',
        'border_crossings_status',
    ];

    protected $casts = [
        'lat' => 'decimal:6',
        'lng' => 'decimal:6',
        'radius_km' => 'decimal:2',
        'event_date' => 'date',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'is_active' => 'boolean',
        'affected_areas' => 'array',
        'impact_assessment' => 'array',
        'travel_recommendations' => 'array',
        'tourism_impact' => 'array',
        'external_sources' => 'array',
        'last_updated' => 'datetime',
        'keywords' => 'array',
        'gdacs_bbox' => 'array',
        'gdacs_resources' => 'array',
        'gdacs_date_added' => 'datetime',
        'gdacs_date_modified' => 'datetime',
        'weather_conditions' => 'array',
        'evacuation_info' => 'array',
        'transportation_impact' => 'array',
        'accommodation_impact' => 'array',
        'communication_status' => 'array',
        'health_services_status' => 'array',
        'utility_services_status' => 'array',
        'border_crossings_status' => 'array',
    ];

    protected $attributes = [
        'is_active' => true,
        'processing_status' => 'pending',
        'severity' => 'medium',
    ];

    /**
     * Get the country for this event.
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Get the event type for this event.
     */
    public function eventType(): BelongsTo
    {
        return $this->belongsTo(EventType::class);
    }

    /**
     * Get the region for this event.
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    /**
     * Get the city for this event.
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /**
     * Scope a query to only include active events.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include events by severity.
     */
    public function scopeBySeverity($query, $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Scope a query to only include events by type.
     */
    public function scopeByEventType($query, $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    /**
     * Scope a query to only include events by country.
     */
    public function scopeByCountry($query, $countryId)
    {
        return $query->where('country_id', $countryId);
    }

    /**
     * Scope a query to only include events by date range.
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('event_date', [$startDate, $endDate]);
    }

    /**
     * Scope a query to only include current GDACS events.
     */
    public function scopeCurrentGdacs($query)
    {
        return $query->where('gdacs_is_current', true);
    }

    /**
     * Get the priority options.
     */
    public static function getPriorityOptions(): array
    {
        return [
            'low' => 'Niedrig',
            'medium' => 'Mittel',
            'high' => 'Hoch',
            'critical' => 'Kritisch',
        ];
    }

    /**
     * Get the severity options.
     */
    public static function getSeverityOptions(): array
    {
        return [
            'low' => 'Niedrig',
            'medium' => 'Mittel',
            'high' => 'Hoch',
            'critical' => 'Kritisch',
        ];
    }

    /**
     * Get the event type options.
     */
    public static function getEventTypeOptions(): array
    {
        return EventType::active()
            ->ordered()
            ->pluck('name', 'id')
            ->toArray();
    }

    /**
     * Get legacy event type options for backward compatibility.
     */
    public static function getLegacyEventTypeOptions(): array
    {
        return [
            'earthquake' => 'Erdbeben',
            'hurricane' => 'Hurrikan',
            'flood' => 'Überschwemmung',
            'wildfire' => 'Waldbrand',
            'volcano' => 'Vulkan',
            'drought' => 'Dürre',
            'tsunami' => 'Tsunami',
            'storm' => 'Sturm',
            'other' => 'Sonstiges',
        ];
    }

    /**
     * Get the processing status options.
     */
    public static function getProcessingStatusOptions(): array
    {
        return [
            'pending' => 'Ausstehend',
            'processing' => 'In Bearbeitung',
            'completed' => 'Abgeschlossen',
            'failed' => 'Fehlgeschlagen',
        ];
    }

    /**
     * Get the GDACS alert level options.
     */
    public static function getGdacsAlertLevelOptions(): array
    {
        return [
            'green' => 'Grün',
            'orange' => 'Orange',
            'red' => 'Rot',
        ];
    }
}

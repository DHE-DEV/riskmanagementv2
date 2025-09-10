<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomEvent extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'event_type',
        'event_type_id',
        'country_id',
        'latitude',
        'longitude',
        'marker_color',
        'marker_icon',
        'icon_color',
        'marker_size',
        'popup_content',
        'start_date',
        'end_date',
        'is_active',
        'priority',
        'severity',
        'category',
        'tags',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'latitude' => 'decimal:16',
        'longitude' => 'decimal:16',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_active' => 'boolean',
        'tags' => 'array',
    ];

    protected $attributes = [
        'marker_color' => '#FF0000',
        'marker_icon' => 'fa-map-marker',
        'icon_color' => '#FFFFFF',
        'marker_size' => 'medium',
        'is_active' => true,
        'priority' => 'medium',
        'severity' => 'medium',
    ];

    /**
     * Country relation.
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Event type relation.
     */
    public function eventType(): BelongsTo
    {
        return $this->belongsTo(EventType::class);
    }

    /**
     * Get the user who created this event.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this event.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the correct event type code, resolving 'general' from EventType relationship
     */
    public function getCorrectEventType()
    {
        // Wenn event_type 'general' ist, hole den korrekten Code aus der Beziehung
        if ($this->attributes['event_type'] === 'general' && $this->event_type_id) {
            $eventType = EventType::find($this->event_type_id);
            return $eventType && $eventType->code ? $eventType->code : 'other';
        }
        
        return $this->attributes['event_type'] ?: 'other';
    }

    /**
     * Set the event_type automatically when event_type_id is set
     */
    public function setEventTypeIdAttribute($value)
    {
        $this->attributes['event_type_id'] = $value;
        
        // Automatisch event_type aus EventType-Beziehung setzen
        if ($value) {
            $eventType = EventType::find($value);
            if ($eventType && $eventType->code) {
                $this->attributes['event_type'] = $eventType->code;
            }
        }
    }

    /**
     * Scope a query to only include active events.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include events within a date range.
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween('start_date', [$startDate, $endDate])
              ->orWhereBetween('end_date', [$startDate, $endDate])
              ->orWhere(function ($subQ) use ($startDate, $endDate) {
                  $subQ->where('start_date', '<=', $startDate)
                       ->where('end_date', '>=', $endDate);
              });
        });
    }

    /**
     * Scope a query to only include events by priority.
     */
    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope a query to only include events by category.
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Get the marker size options.
     */
    public static function getMarkerSizeOptions(): array
    {
        return [
            'small' => 'Klein',
            'medium' => 'Mittel',
            'large' => 'Groß',
        ];
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
            'exercise' => 'Übung',
            'other' => 'Sonstiges',
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
}

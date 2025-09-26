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
        'event_category_id',
        'data_source',
        'data_source_id',
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
        'archived',
        'archived_at',
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
        'archived' => 'boolean',
        'archived_at' => 'datetime',
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
     * Country relation (single - for backward compatibility).
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Countries relation (many-to-many).
     */
    public function countries()
    {
        return $this->belongsToMany(Country::class, 'country_custom_event')
            ->withPivot(['latitude', 'longitude', 'location_note', 'use_default_coordinates'])
            ->withTimestamps();
    }

    /**
     * Event type relation (single - for backward compatibility).
     */
    public function eventType(): BelongsTo
    {
        return $this->belongsTo(EventType::class);
    }

    /**
     * Event types relation (many-to-many).
     */
    public function eventTypes()
    {
        return $this->belongsToMany(EventType::class, 'custom_event_event_type')
            ->withTimestamps();
    }

    /**
     * Event category relation.
     */
    public function eventCategory(): BelongsTo
    {
        return $this->belongsTo(EventCategory::class);
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
     * Scope a query to only include non-archived events.
     */
    public function scopeNotArchived($query)
    {
        return $query->where('archived', false);
    }

    /**
     * Scope a query to only include archived events.
     */
    public function scopeArchived($query)
    {
        return $query->where('archived', true);
    }

    /**
     * Scope a query to include visible events (active and not expired archived).
     * Archived events are visible for 1 year after their end_date.
     */
    public function scopeVisible($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                // Nicht-archivierte Events
                $q->where('archived', false)
                  // Oder archivierte Events, die noch nicht abgelaufen sind
                  ->orWhere(function ($subQ) {
                      $subQ->where('archived', true)
                           ->where(function ($dateQ) {
                               // Events mit Enddatum: 1 Jahr nach Enddatum noch sichtbar
                               $dateQ->whereNotNull('end_date')
                                     ->where('end_date', '>=', now()->subYear())
                               // Events ohne Enddatum: 1 Jahr nach Archivierungsdatum noch sichtbar
                               ->orWhere(function ($archQ) {
                                   $archQ->whereNull('end_date')
                                         ->whereNotNull('archived_at')
                                         ->where('archived_at', '>=', now()->subYear());
                               });
                           });
                  });
            });
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
     * Get the clicks for this event.
     */
    public function clicks()
    {
        return $this->hasMany(EventClick::class);
    }

    /**
     * Get click statistics for this event.
     */
    public function getClickStatistics()
    {
        return [
            'total' => $this->clicks()->count(),
            'list' => $this->clicks()->byType('list')->count(),
            'map_marker' => $this->clicks()->byType('map_marker')->count(),
            'details_button' => $this->clicks()->byType('details_button')->count(),
            'today' => $this->clicks()->whereDate('clicked_at', today())->count(),
            'this_week' => $this->clicks()->whereBetween('clicked_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'this_month' => $this->clicks()->whereMonth('clicked_at', now()->month)->whereYear('clicked_at', now()->year)->count(),
        ];
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
            'info' => 'Information',
            'low' => 'Niedrig',
            'medium' => 'Mittel',
            'high' => 'Hoch',
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
        ];
    }

    /**
     * Archive the event.
     */
    public function archive(): void
    {
        $this->update([
            'archived' => true,
            'archived_at' => now(),
        ]);
    }

    /**
     * Unarchive the event.
     */
    public function unarchive(): void
    {
        $this->update([
            'archived' => false,
            'archived_at' => null,
        ]);
    }

    /**
     * Check if the event is still visible (considering archive rules).
     */
    public function isVisible(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if (!$this->archived) {
            return true;
        }

        // For archived events, check if they're still within the 1-year visibility period
        $referenceDate = $this->end_date ?: $this->archived_at;

        if (!$referenceDate) {
            return false;
        }

        return $referenceDate->gte(now()->subYear());
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Automatically set archived_at when archiving
        static::updating(function ($event) {
            if ($event->isDirty('archived')) {
                if ($event->archived && !$event->archived_at) {
                    $event->archived_at = now();
                } elseif (!$event->archived) {
                    $event->archived_at = null;
                }
            }
        });
    }
}

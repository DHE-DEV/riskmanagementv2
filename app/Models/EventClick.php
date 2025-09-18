<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventClick extends Model
{
    protected $fillable = [
        'custom_event_id',
        'click_type',
        'ip_address',
        'user_agent',
        'session_id',
        'user_id',
        'clicked_at',
    ];

    protected $casts = [
        'clicked_at' => 'datetime',
    ];

    /**
     * Get the custom event that was clicked.
     */
    public function customEvent(): BelongsTo
    {
        return $this->belongsTo(CustomEvent::class);
    }

    /**
     * Get the user who clicked (if authenticated).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get click type label.
     */
    public function getClickTypeLabelAttribute(): string
    {
        return match($this->click_type) {
            'list' => 'Event-Liste',
            'map_marker' => 'Karten-Symbol',
            'details_button' => 'Details-Button',
            default => $this->click_type,
        };
    }

    /**
     * Scope to filter by click type.
     */
    public function scopeByType($query, $type)
    {
        return $query->where('click_type', $type);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('clicked_at', [$startDate, $endDate]);
    }
}

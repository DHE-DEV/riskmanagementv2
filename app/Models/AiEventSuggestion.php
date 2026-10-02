<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Von der KI vorgeschlagenes Thema fuer ein Ereignis – samt Einschaetzung von
 * Prioritaet, Zeitraum, Event-Typen und Laendern. Alle Quellen zum selben
 * Ereignis haengen an EINEM Vorschlag.
 */
class AiEventSuggestion extends Model
{
    public const STATUS_NEW = 'new';

    public const STATUS_DISMISSED = 'dismissed';

    public const STATUS_CONVERTED = 'converted';

    protected $fillable = [
        'search_id', 'title', 'summary', 'priority', 'start_date', 'end_date',
        'event_type_codes', 'country_codes', 'location', 'sources',
        'status', 'custom_event_id', 'handled_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'event_type_codes' => 'array',
        'country_codes' => 'array',
        'sources' => 'array',
    ];

    protected $attributes = [
        'status' => self::STATUS_NEW,
        'priority' => 'medium',
    ];

    public function search(): BelongsTo
    {
        return $this->belongsTo(AiEventSearch::class, 'search_id');
    }

    public function customEvent(): BelongsTo
    {
        return $this->belongsTo(CustomEvent::class, 'custom_event_id')->withTrashed();
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_NEW);
    }
}

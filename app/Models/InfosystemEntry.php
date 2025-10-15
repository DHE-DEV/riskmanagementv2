<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InfosystemEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'api_id',
        'position',
        'appearance',
        'country_code',
        'country_names',
        'lang',
        'language_content',
        'language_code',
        'tagtype',
        'tagtext',
        'tagdate',
        'header',
        'content',
        'archive',
        'active',
        'categories',
        'is_published',
        'published_at',
        'published_as_event_id',
        'api_created_at',
        'request_id',
        'response_time',
    ];

    protected $casts = [
        'country_names' => 'array',
        'categories' => 'array',
        'archive' => 'boolean',
        'active' => 'boolean',
        'is_published' => 'boolean',
        'published_at' => 'datetime',
        'tagdate' => 'date',
        'api_created_at' => 'datetime',
    ];

    protected $attributes = [
        'active' => true,
    ];

    /**
     * Scope to get only active entries
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope to get non-archived entries
     */
    public function scopeNotArchived($query)
    {
        return $query->where('archive', false);
    }

    /**
     * Scope to filter by language
     */
    public function scopeByLanguage($query, $lang = 'de')
    {
        return $query->where('lang', $lang);
    }

    /**
     * Scope to filter by country code
     */
    public function scopeByCountry($query, $countryCode)
    {
        return $query->where('country_code', $countryCode);
    }

    /**
     * Scope to order by tag date descending
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('tagdate', 'desc')->orderBy('created_at', 'desc');
    }

    /**
     * Get country name in specific language
     */
    public function getCountryName($lang = 'de'): ?string
    {
        return $this->country_names[$lang] ?? $this->country_names['en'] ?? null;
    }

    /**
     * Get formatted tag date
     */
    public function getFormattedTagDate(): string
    {
        return $this->tagdate->format('d.m.Y');
    }

    /**
     * Check if entry is recent (within last 7 days)
     */
    public function isRecent(): bool
    {
        return $this->tagdate->isAfter(now()->subDays(7));
    }

    /**
     * Get short content preview (first 150 characters)
     */
    public function getPreview(): string
    {
        return \Str::limit($this->content, 150);
    }

    /**
     * Get the published event
     */
    public function publishedEvent(): BelongsTo
    {
        return $this->belongsTo(CustomEvent::class, 'published_as_event_id');
    }

    /**
     * Scope to get only published entries
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    /**
     * Scope to get only unpublished entries
     */
    public function scopeUnpublished($query)
    {
        return $query->where('is_published', false);
    }
}

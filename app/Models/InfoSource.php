<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InfoSource extends Model
{
    use SoftDeletes;

    /**
     * Get the items fetched from this source.
     */
    public function items(): HasMany
    {
        return $this->hasMany(InfoSourceItem::class);
    }

    protected $fillable = [
        'name',
        'code',
        'description',
        'type',
        'url',
        'api_endpoint',
        'api_key',
        'api_config',
        'content_type',
        'country_code',
        'language',
        'refresh_interval',
        'is_active',
        'auto_import',
        'last_fetched_at',
        'last_error_at',
        'last_error_message',
        'sort_order',
    ];

    protected $casts = [
        'api_config' => 'array',
        'is_active' => 'boolean',
        'auto_import' => 'boolean',
        'last_fetched_at' => 'datetime',
        'last_error_at' => 'datetime',
        'refresh_interval' => 'integer',
        'sort_order' => 'integer',
    ];

    /**
     * Get the type label.
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'rss' => 'RSS Feed',
            'api' => 'JSON API',
            'rss_api' => 'RSS + API',
            default => $this->type,
        };
    }

    /**
     * Get the content type label.
     */
    public function getContentTypeLabelAttribute(): string
    {
        return match($this->content_type) {
            'travel_advisory' => 'Reisewarnungen',
            'health' => 'Gesundheit',
            'disaster' => 'Naturkatastrophen',
            'conflict' => 'Konflikte & Unruhen',
            'general' => 'Allgemein',
            default => $this->content_type,
        };
    }

    /**
     * Check if the source has an error.
     */
    public function hasError(): bool
    {
        return $this->last_error_at !== null &&
               ($this->last_fetched_at === null || $this->last_error_at > $this->last_fetched_at);
    }

    /**
     * Check if the source needs refresh.
     */
    public function needsRefresh(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->last_fetched_at === null) {
            return true;
        }

        return $this->last_fetched_at->addSeconds($this->refresh_interval)->isPast();
    }

    /**
     * Mark as fetched successfully.
     */
    public function markAsFetched(): void
    {
        $this->update([
            'last_fetched_at' => now(),
            'last_error_at' => null,
            'last_error_message' => null,
        ]);
    }

    /**
     * Mark as error.
     */
    public function markAsError(string $message): void
    {
        $this->update([
            'last_error_at' => now(),
            'last_error_message' => $message,
        ]);
    }

    /**
     * Scope for active sources.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for sources that need refresh.
     */
    public function scopeNeedsRefresh($query)
    {
        return $query->active()
            ->where(function ($q) {
                $q->whereNull('last_fetched_at')
                    ->orWhereRaw('last_fetched_at < NOW() - INTERVAL refresh_interval SECOND');
            });
    }

    /**
     * Scope for ordered sources.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}

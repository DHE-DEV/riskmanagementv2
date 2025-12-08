<?php

namespace App\Models\TravelDetail;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class TdPdsShareLink extends Model
{
    protected $table = 'td_pds_share_links';

    public $timestamps = false;

    protected $fillable = [
        'trip_id',
        'share_url',
        'tid',
        'created_at',
        'expires_at',
        'view_count',
        'last_viewed_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'expires_at' => 'datetime',
        'last_viewed_at' => 'datetime',
        'view_count' => 'integer',
    ];

    /**
     * The trip this share link belongs to
     */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(TdTrip::class, 'trip_id');
    }

    /**
     * Scope: Active (not expired) links
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Scope: Expired links
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->whereNotNull('expires_at')
            ->where('expires_at', '<=', now());
    }

    /**
     * Check if link is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if link is active
     */
    public function isActive(): bool
    {
        return !$this->isExpired();
    }

    /**
     * Increment view count and update last_viewed_at
     */
    public function recordView(): void
    {
        $this->increment('view_count');
        $this->update(['last_viewed_at' => now()]);
    }

    /**
     * Get formatted TID (XXXX-XXXX-XXXX)
     */
    public function getFormattedTidAttribute(): string
    {
        $tid = preg_replace('/[^A-Z0-9]/i', '', $this->tid);

        if (strlen($tid) >= 12) {
            return substr($tid, 0, 4) . '-' . substr($tid, 4, 4) . '-' . substr($tid, 8, 4);
        }

        return $this->tid;
    }

    /**
     * Get days until expiration
     */
    public function getDaysUntilExpirationAttribute(): ?int
    {
        if (!$this->expires_at) {
            return null;
        }

        if ($this->expires_at->isPast()) {
            return 0;
        }

        return now()->diffInDays($this->expires_at);
    }
}

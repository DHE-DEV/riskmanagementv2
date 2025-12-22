<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class InfoSourceItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'info_source_id',
        'external_id',
        'title',
        'description',
        'content',
        'link',
        'author',
        'categories',
        'countries',
        'published_at',
        'updated_at_source',
        'status',
        'imported_as_event_id',
        'raw_data',
    ];

    protected $casts = [
        'categories' => 'array',
        'countries' => 'array',
        'raw_data' => 'array',
        'published_at' => 'datetime',
        'updated_at_source' => 'datetime',
    ];

    /**
     * Get the info source.
     */
    public function infoSource(): BelongsTo
    {
        return $this->belongsTo(InfoSource::class);
    }

    /**
     * Get the imported event.
     */
    public function importedEvent(): BelongsTo
    {
        return $this->belongsTo(CustomEvent::class, 'imported_as_event_id');
    }

    /**
     * Get status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'new' => 'Neu',
            'reviewed' => 'Geprüft',
            'imported' => 'Importiert',
            'ignored' => 'Ignoriert',
            default => $this->status,
        };
    }

    /**
     * Get status color.
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'new' => 'info',
            'reviewed' => 'warning',
            'imported' => 'success',
            'ignored' => 'gray',
            default => 'gray',
        };
    }

    /**
     * Check if item is new.
     */
    public function isNew(): bool
    {
        return $this->status === 'new';
    }

    /**
     * Check if item was imported.
     */
    public function isImported(): bool
    {
        return $this->status === 'imported' && $this->imported_as_event_id !== null;
    }

    /**
     * Mark as reviewed.
     */
    public function markAsReviewed(): void
    {
        $this->update(['status' => 'reviewed']);
    }

    /**
     * Mark as ignored.
     */
    public function markAsIgnored(): void
    {
        $this->update(['status' => 'ignored']);
    }

    /**
     * Mark as imported.
     */
    public function markAsImported(int $eventId): void
    {
        $this->update([
            'status' => 'imported',
            'imported_as_event_id' => $eventId,
        ]);
    }

    /**
     * Scope for new items.
     */
    public function scopeNew($query)
    {
        return $query->where('status', 'new');
    }

    /**
     * Scope for unprocessed items.
     */
    public function scopeUnprocessed($query)
    {
        return $query->whereIn('status', ['new', 'reviewed']);
    }

    /**
     * Scope ordered by date.
     */
    public function scopeLatest($query)
    {
        return $query->orderByDesc('published_at')->orderByDesc('created_at');
    }
}

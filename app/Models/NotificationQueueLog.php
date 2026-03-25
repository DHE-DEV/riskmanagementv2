<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class NotificationQueueLog extends Model
{
    protected $fillable = [
        'queue_name',
        'started_at',
        'completed_at',
        'events_processed',
        'notifications_sent',
        'errors',
        'status',
        'error_message',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'events_processed' => 'integer',
        'notifications_sent' => 'integer',
        'errors' => 'integer',
    ];

    public function scopeForQueue(Builder $query, string $name): Builder
    {
        return $query->where('queue_name', $name);
    }

    public function getDurationAttribute(): ?string
    {
        if (!$this->completed_at || !$this->started_at) {
            return null;
        }

        $seconds = $this->started_at->diffInSeconds($this->completed_at);

        if ($seconds < 60) {
            return $seconds . 's';
        }

        return floor($seconds / 60) . 'm ' . ($seconds % 60) . 's';
    }
}

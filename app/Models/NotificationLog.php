<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationLog extends Model
{
    protected $fillable = [
        'notification_rule_id',
        'customer_id',
        'event_id',
        'event_type',
        'recipient_email',
        'subject',
        'status',
        'error_message',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function notificationRule(): BelongsTo
    {
        return $this->belongsTo(NotificationRule::class);
    }

    public function scopeForEvent(Builder $query, int $eventId, string $eventType): Builder
    {
        return $query->where('event_id', $eventId)->where('event_type', $eventType);
    }

    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }
}

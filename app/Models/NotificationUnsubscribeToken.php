<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class NotificationUnsubscribeToken extends Model
{
    protected $fillable = [
        'token',
        'email',
        'notification_rule_id',
        'customer_id',
        'unsubscribed_at',
    ];

    protected $casts = [
        'unsubscribed_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function notificationRule(): BelongsTo
    {
        return $this->belongsTo(NotificationRule::class);
    }

    public static function generateFor(string $email, int $customerId, ?int $ruleId = null): self
    {
        return self::create([
            'token' => Str::random(64),
            'email' => $email,
            'customer_id' => $customerId,
            'notification_rule_id' => $ruleId,
        ]);
    }

    public function isUsed(): bool
    {
        return $this->unsubscribed_at !== null;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('unsubscribed_at');
    }

    public function scopeForEmail(Builder $query, string $email): Builder
    {
        return $query->where('email', $email);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotificationTemplate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'customer_id',
        'name',
        'subject',
        'body_html',
        'is_system',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    public const PLACEHOLDERS = [
        '{event_title}' => 'Titel des Ereignisses',
        '{country_name}' => 'Name des Landes',
        '{risk_level}' => 'Risikostufe',
        '{category}' => 'Kategorie',
        '{description}' => 'Beschreibung des Ereignisses',
        '{event_date}' => 'Datum des Ereignisses',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function notificationRules(): HasMany
    {
        return $this->hasMany(NotificationRule::class);
    }

    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    public function scopeForCustomer($query, int $customerId)
    {
        return $query->where(function ($q) use ($customerId) {
            $q->where('customer_id', $customerId)
              ->orWhere('is_system', true);
        });
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;

class NotificationTemplate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'customer_id',
        'source',
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
        '{affected_trips}' => 'Betroffene Reisen (HTML-Block, nur Travel Alert)',
        '{affected_trips_count}' => 'Anzahl betroffener Reisen',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function notificationRules(): HasMany
    {
        return $this->hasMany(NotificationRule::class);
    }

    public function scopeSystem($query, ?string $source = null)
    {
        $query->where('is_system', true);

        if ($source && Schema::hasColumn('notification_templates', 'source')) {
            $query->where('source', $source);
        }

        return $query;
    }

    public function scopeForCustomer($query, int $customerId, ?string $source = null)
    {
        $hasSource = $source && Schema::hasColumn('notification_templates', 'source');

        return $query->where(function ($q) use ($customerId, $hasSource, $source) {
            $q->where(function ($cq) use ($customerId, $hasSource, $source) {
                $cq->where('customer_id', $customerId);
                if ($hasSource) {
                    $cq->where('source', $source);
                }
            })->orWhere(function ($sq) use ($hasSource, $source) {
                $sq->where('is_system', true);
                if ($hasSource) {
                    $sq->where('source', $source);
                }
            });
        });
    }
}

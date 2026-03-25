<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotificationRule extends Model
{
    use SoftDeletes;

    public const SOURCE_TRAVEL_ALERT = 'travel-alert';
    public const SOURCE_GLOBAL_TRAVEL_MONITOR = 'global-travel-monitor';

    protected $fillable = [
        'customer_id',
        'source',
        'name',
        'is_active',

        'risk_levels',
        'categories',
        'country_ids',
        'notification_template_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'risk_levels' => 'array',
        'categories' => 'array',
        'country_ids' => 'array',
    ];

    public const RISK_LEVELS = [
        'high' => 'Hoch',
        'medium' => 'Mittel',
        'low' => 'Niedrig',
        'info' => 'Information',
    ];

    public const CATEGORIES = [
        'environment' => 'Umweltereignisse',
        'traffic' => 'Reiseverkehr',
        'security' => 'Sicherheit',
        'entry' => 'Einreisebestimmungen',
        'health' => 'Gesundheit',
        'general' => 'Allgemein',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(NotificationRuleRecipient::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(NotificationTemplate::class, 'notification_template_id');
    }

    public function getRiskLevelLabelsAttribute(): array
    {
        return collect($this->risk_levels ?? [])
            ->map(fn ($level) => self::RISK_LEVELS[$level] ?? $level)
            ->toArray();
    }

    public function getCategoryLabelsAttribute(): array
    {
        return collect($this->categories ?? [])
            ->map(fn ($cat) => self::CATEGORIES[$cat] ?? $cat)
            ->toArray();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventDisplaySetting extends Model
{
    protected $fillable = [
        'multi_event_icon_strategy',
        'multi_event_type_id',
        'show_icon_preview_in_form',
        'strategy_description',
    ];

    protected $casts = [
        'show_icon_preview_in_form' => 'boolean',
    ];

    /**
     * Get the multi-event type.
     */
    public function multiEventType(): BelongsTo
    {
        return $this->belongsTo(EventType::class, 'multi_event_type_id');
    }

    /**
     * Get the singleton settings instance (always fresh from DB).
     */
    public static function current(): self
    {
        // Immer frisch aus der DB laden, ohne Cache
        $settings = static::query()->first();

        if (!$settings) {
            $settings = static::create([
                'multi_event_icon_strategy' => 'default',
                'show_icon_preview_in_form' => true,
            ]);
        }

        return $settings->fresh();
    }

    /**
     * Get strategy options for select field.
     */
    public static function getStrategyOptions(): array
    {
        return [
            'default' => 'Standard (erstes Icon verwenden)',
            'manual_select' => 'Manuell auswählen (Dropdown im Formular)',
            'multi_event_type' => 'Multi-Event Icon verwenden',
            'show_all' => 'Alle Icons anzeigen (gestapelt auf Karte)',
            'show_icon_preview' => 'Nur Vorschau im Formular',
        ];
    }

    /**
     * Check if manual selection should be shown in form.
     */
    public function shouldShowManualSelection(): bool
    {
        return $this->multi_event_icon_strategy === 'manual_select';
    }

    /**
     * Check if icon preview should be shown in form.
     */
    public function shouldShowIconPreview(): bool
    {
        return $this->show_icon_preview_in_form ||
               $this->multi_event_icon_strategy === 'show_icon_preview';
    }

    /**
     * Check if multi-event type icon should be used.
     */
    public function shouldUseMultiEventType(): bool
    {
        return $this->multi_event_icon_strategy === 'multi_event_type' &&
               $this->multi_event_type_id !== null;
    }

    /**
     * Check if all icons should be shown on map.
     */
    public function shouldShowAllIcons(): bool
    {
        return $this->multi_event_icon_strategy === 'show_all';
    }
}

<?php

namespace App\Models;

use App\Observers\CustomerFeatureOverrideObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy([CustomerFeatureOverrideObserver::class])]
class CustomerFeatureOverride extends Model
{
    protected $fillable = [
        'customer_id',
        'navigation_events_enabled',
        'navigation_entry_conditions_enabled',
        'navigation_booking_enabled',
        'navigation_airports_enabled',
        'navigation_branches_enabled',
        'navigation_my_travelers_enabled',
        'navigation_risk_overview_enabled',
        'navigation_cruise_enabled',
        'navigation_business_visa_enabled',
        'navigation_center_map_enabled',
    ];

    protected $casts = [
        'navigation_events_enabled' => 'boolean',
        'navigation_entry_conditions_enabled' => 'boolean',
        'navigation_booking_enabled' => 'boolean',
        'navigation_airports_enabled' => 'boolean',
        'navigation_branches_enabled' => 'boolean',
        'navigation_my_travelers_enabled' => 'boolean',
        'navigation_risk_overview_enabled' => 'boolean',
        'navigation_cruise_enabled' => 'boolean',
        'navigation_business_visa_enabled' => 'boolean',
        'navigation_center_map_enabled' => 'boolean',
    ];

    /**
     * Get the customer that owns this override.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the list of all feature keys.
     */
    public static function getFeatureKeys(): array
    {
        return [
            'navigation_events_enabled',
            'navigation_entry_conditions_enabled',
            'navigation_booking_enabled',
            'navigation_airports_enabled',
            'navigation_branches_enabled',
            'navigation_my_travelers_enabled',
            'navigation_risk_overview_enabled',
            'navigation_cruise_enabled',
            'navigation_business_visa_enabled',
            'navigation_center_map_enabled',
        ];
    }

    /**
     * Get feature labels for display.
     */
    public static function getFeatureLabels(): array
    {
        return [
            'navigation_events_enabled' => 'Ereignisse',
            'navigation_entry_conditions_enabled' => 'Einreisebestimmungen',
            'navigation_booking_enabled' => 'Buchung',
            'navigation_airports_enabled' => 'Flughäfen',
            'navigation_branches_enabled' => 'Filialen',
            'navigation_my_travelers_enabled' => 'Meine Reisenden',
            'navigation_risk_overview_enabled' => 'Risiko-Übersicht',
            'navigation_cruise_enabled' => 'Kreuzfahrten',
            'navigation_business_visa_enabled' => 'Business Visum',
            'navigation_center_map_enabled' => 'Karte zentrieren',
        ];
    }
}

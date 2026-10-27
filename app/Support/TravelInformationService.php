<?php

namespace App\Support;

/**
 * Bestandteile des Travel Information Service – die Module, die Kunden
 * abonnieren und fuer die sich z. B. das Abrechnungsintervall einzeln
 * festlegen laesst.
 */
class TravelInformationService
{
    public const MODULES = [
        'travel_requirements' => 'Travel Requirements Service',
        'global_travel_monitor' => 'Global Travel Monitor',
        'travel_alert' => 'Travel Alert',
        'airport_data' => 'Airport Data',
        'airline_data' => 'Airline Data',
        'country_data' => 'Country Data',
        'destination_data' => 'Destination Data',
        'attraction_data' => 'Attraction Data',
        'business_visa' => 'Business Visum',
        'visa_service_link' => 'Visum Service Link',
        'wallet' => 'Wallet',
        'travel_details_website' => 'Travel-Details Website',
        'tripdata_app' => 'TripData App',
    ];

    public static function moduleLabel(?string $module): string
    {
        return $module === null ? 'Alle abonnierten Dienste' : (self::MODULES[$module] ?? $module);
    }
}

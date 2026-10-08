<?php

namespace App\Support\AdminV2;

use App\Models\Airline;

/**
 * Wo KI-Pruefungen eingebunden sind: Bereiche (Stammdaten-Arten), ihre
 * Abschnitte im Formular und die Platzhalter, die dort zur Verfuegung stehen.
 * Die Formulare liefern zur Laufzeit die Werte zu genau diesen Schluesseln.
 */
class AiAreas
{
    /** Pseudo-Abschnitt fuer die Schaltflaeche im Kopf des Formulars – bietet alle Pruefungen des Bereichs. */
    public const GENERAL = 'general';

    /** Platzhalter, der alle uebergebenen Daten als Liste einsetzt. */
    public const DATA_PLACEHOLDER = 'daten';

    /**
     * @return array<string, array{label: string, sections: array<string, array{label: string, placeholders: array<string, string>}>}>
     */
    public static function areas(): array
    {
        $coordinates = ['lat' => 'Breitengrad', 'lng' => 'Längengrad'];
        $extras = [
            'lounges' => ['label' => 'Lounges', 'placeholders' => ['lounges' => 'Lounges (Liste)']],
            'mobility' => ['label' => 'Mobilitätsangebote', 'placeholders' => ['mobility' => 'Mobilitätsangebote']],
            'hotels' => ['label' => 'Hotels in der Nähe', 'placeholders' => ['hotels' => 'Hotels (Liste)']],
            'airlines' => ['label' => 'Airlines', 'placeholders' => ['airlines' => 'Verknüpfte Airlines (Liste)']],
        ];

        return [
            'continents' => [
                'label' => 'Kontinente',
                'sections' => [
                    'basics' => ['label' => 'Kontinent', 'placeholders' => ['name' => 'Name', 'name_en' => 'Name (Englisch)', 'code' => 'Code', 'sort_order' => 'Sortierung', 'description' => 'Beschreibung', 'keywords' => 'Schlagwörter']],
                    'coordinates' => ['label' => 'Koordinaten', 'placeholders' => $coordinates],
                    'countries' => ['label' => 'Länder', 'placeholders' => ['countries_count' => 'Anzahl Länder', 'countries' => 'Länder (Liste)']],
                ],
            ],
            'countries' => [
                'label' => 'Länder',
                'sections' => [
                    'basics' => ['label' => 'Grunddaten', 'placeholders' => ['name' => 'Name', 'name_en' => 'Name (Englisch)', 'names' => 'Weitere Sprachen', 'iso_code' => 'ISO-Code (2)', 'iso3_code' => 'ISO-Code (3)', 'continent' => 'Kontinent', 'is_eu_member' => 'EU-Mitglied', 'is_schengen_member' => 'Schengen-Mitglied']],
                    'description' => ['label' => 'Länderbeschreibung', 'placeholders' => CountryTravelInfo::descriptionPlaceholders()],
                    'details' => ['label' => 'Weitere Informationen', 'placeholders' => ['currency_code' => 'Währungscode', 'currency_name' => 'Währungsname', 'currency_symbol' => 'Währungssymbol', 'phone_prefix' => 'Telefonvorwahl', 'timezone' => 'Zeitzone', 'population' => 'Bevölkerung', 'area_km2' => 'Fläche (km²)'] + CountryTravelInfo::placeholders()],
                    'coordinates' => ['label' => 'Koordinaten', 'placeholders' => $coordinates],
                ] + collect(CountryRiskProfile::categories())->mapWithKeys(fn (array $definition, string $category) => [
                    CountryRiskProfile::section($category) => ['label' => $definition['label'], 'placeholders' => CountryRiskProfile::placeholdersFor($category)],
                ])->all() + [
                    'tipping' => ['label' => 'Trinkgeld', 'placeholders' => CountryTravelInfo::tippingPlaceholders()],
                    'power' => ['label' => 'Strom', 'placeholders' => CountryTravelInfo::powerPlaceholders()],
                    'taxi_apps' => ['label' => 'Taxi-Apps', 'placeholders' => ['taxi_apps' => 'Zugeordnete Taxi-Apps (Liste)', 'taxi_apps_available' => 'Verfügbare Taxi-Apps (Liste)']],
                    'mobile_operators' => ['label' => 'Mobilfunkanbieter', 'placeholders' => ['mobile_operators' => 'Zugeordnete Mobilfunkanbieter (Liste)', 'mobile_operators_available' => 'Verfügbare Mobilfunkanbieter (Liste)']],
                    'holidays' => ['label' => 'Feiertage', 'placeholders' => ['holidays_year' => 'Jahr', 'holidays_count' => 'Anzahl Feiertage', 'holidays' => 'Feiertage (Liste mit Datum, Namen, Kommentar)']],
                    'images' => ['label' => 'Bilder', 'placeholders' => ['flag' => 'Flagge', 'images_count' => 'Anzahl Bilder', 'images' => 'Bilder (Liste mit Alt-Text, Bildunterschrift, Urheber)']],
                ],
            ],
            'regions' => [
                'label' => 'Regionen',
                'sections' => [
                    'basics' => ['label' => 'Region', 'placeholders' => ['name' => 'Name', 'name_en' => 'Name (Englisch)', 'code' => 'Code', 'country' => 'Land', 'description' => 'Beschreibung', 'keywords' => 'Schlagwörter']],
                    'coordinates' => ['label' => 'Koordinaten', 'placeholders' => $coordinates],
                    'cities' => ['label' => 'Städte', 'placeholders' => ['cities_count' => 'Anzahl Städte', 'cities' => 'Städte (Liste)']],
                ],
            ],
            'cities' => [
                'label' => 'Städte',
                'sections' => [
                    'basics' => ['label' => 'Stadt', 'placeholders' => ['name' => 'Name', 'name_en' => 'Name (Englisch)', 'country' => 'Land', 'region' => 'Region', 'is_capital' => 'Hauptstadt', 'is_regional_capital' => 'Regionshauptstadt', 'population' => 'Bevölkerung']],
                    'coordinates' => ['label' => 'Koordinaten', 'placeholders' => $coordinates],
                ],
            ],
            'airports' => [
                'label' => 'Flughäfen',
                'sections' => [
                    'basics' => ['label' => 'Flughafen', 'placeholders' => ['name' => 'Name', 'iata_code' => 'IATA-Code', 'icao_code' => 'ICAO-Code', 'country' => 'Land', 'city' => 'Stadt', 'type' => 'Typ', 'website' => 'Website', 'security_timeslot_url' => 'Zeitfenster-Reservierung', 'is_active' => 'Aktiv', 'operates_24h' => '24-Stunden-Betrieb']],
                    'coordinates' => ['label' => 'Koordinaten', 'placeholders' => $coordinates],
                    'altitude' => ['label' => 'Höhe und Zeitzone', 'placeholders' => ['altitude' => 'Höhe (m)', 'timezone' => 'Zeitzone', 'dst_timezone' => 'Sommerzeit-Zeitzone']],
                ] + $extras,
            ],
            'airport-codes' => [
                'label' => 'Flughafen-Codes',
                'sections' => [
                    'basics' => ['label' => 'Flugplatz', 'placeholders' => ['name' => 'Name', 'country' => 'Land', 'city' => 'Stadt', 'iso_country' => 'Land (ISO)', 'iso_region' => 'Region (ISO)', 'municipality' => 'Ort', 'website' => 'Website', 'security_timeslot_url' => 'Zeitfenster-Reservierung', 'is_active' => 'Aktiv', 'operates_24h' => '24-Stunden-Betrieb']],
                    'codes' => ['label' => 'Codes und Einstufung', 'placeholders' => ['ident' => 'Ident', 'iata_code' => 'IATA-Code', 'icao_code' => 'ICAO-Code', 'gps_code' => 'GPS-Code', 'local_code' => 'Lokaler Code', 'type' => 'Typ', 'continent' => 'Kontinent', 'scheduled_service' => 'Linienflugverkehr']],
                    'coordinates' => ['label' => 'Koordinaten', 'placeholders' => $coordinates],
                    'altitude' => ['label' => 'Höhe und Zeitzone', 'placeholders' => ['elevation_ft' => 'Höhe (ft)', 'timezone' => 'Zeitzone', 'dst_timezone' => 'Sommerzeit-Zeitzone']],
                    'links' => ['label' => 'Links und Suchbegriffe', 'placeholders' => ['home_link' => 'Home-Link', 'wikipedia_link' => 'Wikipedia-Link', 'keywords' => 'Suchbegriffe', 'source' => 'Datenquelle']],
                ] + $extras,
            ],
            'airlines' => [
                'label' => 'Airlines',
                'sections' => [
                    'basics' => ['label' => 'Airline', 'placeholders' => ['name' => 'Name', 'iata_code' => 'IATA-Code', 'icao_code' => 'ICAO-Code', 'home_country' => 'Heimatland', 'headquarters' => 'Hauptsitz', 'is_active' => 'Aktiv', 'website' => 'Website', 'booking_url' => 'Buchungslink', 'contact' => 'Kontaktmöglichkeiten']],
                    'cabin_classes' => ['label' => 'Kabinenklassen', 'placeholders' => ['cabin_classes' => 'Kabinenklassen']],
                    'baggage' => ['label' => 'Freigepäck & Handgepäck', 'placeholders' => ['baggage' => 'Gepäckregeln (alle Angaben)'] + self::baggagePlaceholders()],
                    'pets' => ['label' => 'Haustiermitnahme', 'placeholders' => ['pets' => 'Haustiermitnahme (alle Angaben)'] + self::petPlaceholders()],
                    'airports' => ['label' => 'Flughäfen', 'placeholders' => ['airports' => 'Direktverbindungen (Liste)']],
                ],
            ],
        ];
    }

    /**
     * Die Gepaeckfelder einer Airline, einzeln – je Kabinenklasse Freigepaeck,
     * Handgepaeck und dessen Masse, dazu Hinweise und Info-URL.
     *
     * @return array<string, string>
     */
    public static function baggagePlaceholders(): array
    {
        $classes = Airline::getCabinClassOptions();
        $fields = [];

        foreach ($classes as $class => $label) {
            $fields['baggage_checked_'.$class] = 'Freigepäck › '.$label;
        }

        foreach ($classes as $class => $label) {
            $fields['baggage_hand_'.$class] = 'Handgepäck › '.$label.' – Gewicht';
            $fields['baggage_hand_'.$class.'_length'] = 'Handgepäck › '.$label.' – Länge (cm)';
            $fields['baggage_hand_'.$class.'_width'] = 'Handgepäck › '.$label.' – Breite (cm)';
            $fields['baggage_hand_'.$class.'_height'] = 'Handgepäck › '.$label.' – Höhe (cm)';
        }

        return $fields + ['baggage_notes' => 'Allgemeine Hinweise', 'baggage_info_url' => 'Info-URL'];
    }

    /**
     * Die Felder der Haustiermitnahme einer Airline, einzeln – so wie sie im Formular stehen.
     *
     * @return array<string, string>
     */
    public static function petPlaceholders(): array
    {
        return [
            'pets_allowed' => 'Haustiermitnahme erlaubt',
            'pets_cabin_allowed' => 'In der Kabine › Erlaubt',
            'pets_cabin_max_weight' => 'In der Kabine › Maximales Gewicht',
            'pets_cabin_weight_includes_bag' => 'In der Kabine › Gewicht inklusive Tasche',
            'pets_cabin_carrier_length' => 'In der Kabine › Transportbox-Länge (cm)',
            'pets_cabin_carrier_width' => 'In der Kabine › Transportbox-Breite (cm)',
            'pets_cabin_carrier_height' => 'In der Kabine › Transportbox-Höhe (cm)',
            'pets_cabin_advance_notice_required' => 'In der Kabine › Voranmeldung erforderlich',
            'pets_cabin_notes' => 'In der Kabine › Zusätzliche Hinweise',
            'pets_hold_allowed' => 'Im Frachtraum › Erlaubt',
            'pets_hold_max_weight' => 'Im Frachtraum › Maximales Gewicht',
            'pets_hold_advance_notice_required' => 'Im Frachtraum › Voranmeldung erforderlich',
            'pets_hold_notes' => 'Im Frachtraum › Zusätzliche Hinweise',
            'pets_restrictions' => 'Allgemeine Einschränkungen',
            'pets_info_url' => 'Info-URL',
            'pets_notes' => 'Allgemeine Hinweise',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return array_map(fn (array $area) => $area['label'], self::areas());
    }

    public static function label(string $area): string
    {
        return self::areas()[$area]['label'] ?? $area;
    }

    /**
     * @return array<string, string>
     */
    public static function sectionLabels(string $area): array
    {
        return array_map(fn (array $section) => $section['label'], self::areas()[$area]['sections'] ?? []);
    }

    public static function sectionLabel(string $area, string $section): string
    {
        return $section === self::GENERAL ? 'Gesamter Eintrag' : (self::areas()[$area]['sections'][$section]['label'] ?? $section);
    }

    /**
     * Platzhalter eines Abschnitts; fuer "general" die aller Abschnitte.
     *
     * @return array<string, string>
     */
    public static function placeholders(string $area, ?string $section): array
    {
        $sections = self::areas()[$area]['sections'] ?? [];

        if ($section === null || $section === self::GENERAL) {
            return array_merge([], ...array_values(array_column($sections, 'placeholders')));
        }

        return $sections[$section]['placeholders'] ?? [];
    }
}

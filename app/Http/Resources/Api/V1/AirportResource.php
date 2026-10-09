<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Airline;
use App\Models\Airport;

/**
 * Flughafen fuer die Kunden-API: Kurzform fuer Listen, Langform mit Lounges,
 * Hotels in der Naehe, Mobilitaet und den dort fliegenden Airlines.
 */
class AirportResource
{
    public const TYPES = [
        'international' => 'Internationaler Flughafen',
        'large_airport' => 'Großer Flughafen',
        'medium_airport' => 'Mittlerer Flughafen',
        'small_airport' => 'Kleiner Flughafen',
    ];

    /**
     * @return array<string, mixed>
     */
    public static function summary(Airport $airport, ?string $lang): array
    {
        return [
            'iata_code' => $airport->iata_code,
            'icao_code' => $airport->icao_code,
            'name' => $airport->name,
            'type' => $airport->type,
            'type_label' => self::TYPES[$airport->type] ?? null,
            'city' => $airport->city ? self::text($airport->city->name_translations, $lang) : null,
            'country' => $airport->country ? [
                'iso_code' => $airport->country->iso_code,
                'name' => self::text($airport->country->name_translations, $lang),
            ] : null,
            'coordinates' => $airport->lat !== null && $airport->lng !== null ? ['lat' => (float) $airport->lat, 'lng' => (float) $airport->lng] : null,
            'timezone' => $airport->timezone,
            'operates_24h' => (bool) $airport->operates_24h,
            'website_url' => $airport->website,
            'security_timeslot_url' => $airport->security_timeslot_url,
            'counts' => [
                'lounges' => count(self::lounges($airport)),
                'nearby_hotels' => count(self::hotels($airport)),
                'airlines' => $airport->relationLoaded('airlines') ? $airport->airlines->count() : ($airport->airlines_count ?? null),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function detail(Airport $airport, ?string $lang): array
    {
        return array_merge(self::summary($airport, $lang), [
            'altitude_m' => $airport->altitude,
            'lounges' => self::lounges($airport),
            'nearby_hotels' => self::hotels($airport),
            'mobility' => self::mobility($airport),
            'airlines' => $airport->airlines->map(fn (Airline $airline) => [
                'iata_code' => $airline->iata_code,
                'icao_code' => $airline->icao_code,
                'name' => trim((string) $airline->name),
                'terminal' => $airline->pivot->terminal,
                'direction' => $airline->pivot->direction,
                'cabin_classes' => AirlineResource::cabinClasses($airline),
            ])->values()->all(),
            'updated_at' => $airport->updated_at?->toIso8601String(),
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function lounges(Airport $airport): array
    {
        return collect($airport->lounges ?? [])
            ->filter(fn ($lounge) => is_array($lounge) && ! empty($lounge['name']))
            ->map(fn (array $lounge) => [
                'name' => $lounge['name'],
                'location' => $lounge['location'] ?? null,
                'access' => $lounge['access'] ?? null,
                'price_per_person' => isset($lounge['price_per_person']) && $lounge['price_per_person'] !== '' ? (float) $lounge['price_per_person'] : null,
                'children_welcome' => isset($lounge['children_welcome']) ? (bool) $lounge['children_welcome'] : null,
                'url' => $lounge['url'] ?? null,
            ])->values()->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function hotels(Airport $airport): array
    {
        return collect($airport->nearby_hotels ?? [])
            ->filter(fn ($hotel) => is_array($hotel) && ! empty($hotel['name']))
            ->map(fn (array $hotel) => [
                'name' => $hotel['name'],
                'distance_km' => isset($hotel['distance_km']) && $hotel['distance_km'] !== '' ? (float) $hotel['distance_km'] : null,
                'shuttle' => isset($hotel['shuttle']) ? (bool) $hotel['shuttle'] : null,
                'booking_url' => $hotel['booking_url'] ?? null,
                'notes' => $hotel['notes'] ?? null,
            ])->values()->all();
    }

    /**
     * Mobilitaet am Flughafen – alle fuenf Bereiche immer vorhanden, fehlende als "nicht verfuegbar".
     *
     * @return array<string, mixed>
     */
    public static function mobility(Airport $airport): array
    {
        $m = is_array($airport->mobility_options) ? $airport->mobility_options : [];
        $links = fn ($items) => collect(is_array($items) ? $items : [])
            ->filter(fn ($item) => is_array($item) && ! empty($item['name']))
            ->map(fn (array $item) => array_filter([
                'name' => $item['name'],
                'url' => $item['url'] ?? null,
                'distance' => $item['distance'] ?? null,
                'price_info' => $item['price_info'] ?? null,
            ], fn ($value) => $value !== null))
            ->values()->all();

        return [
            'taxi' => [
                'available' => (bool) ($m['taxi']['available'] ?? false),
                'info' => $m['taxi']['info'] ?? null,
                'approx_cost' => $m['taxi']['approx_cost'] ?? null,
            ],
            'parking' => [
                'available' => (bool) ($m['parking']['available'] ?? false),
                'options' => $links($m['parking']['options'] ?? []),
            ],
            'car_rental' => [
                'available' => (bool) ($m['car_rental']['available'] ?? false),
                'providers' => $links($m['car_rental']['providers'] ?? []),
            ],
            'airport_shuttle' => [
                'available' => (bool) ($m['airport_shuttle']['available'] ?? false),
                'info' => $m['airport_shuttle']['info'] ?? null,
                'url' => $m['airport_shuttle']['url'] ?? null,
            ],
            'public_transport' => [
                'available' => (bool) ($m['public_transport']['available'] ?? false),
                'types' => $links($m['public_transport']['types'] ?? []),
            ],
        ];
    }

    /**
     * Mehrsprachiger Text: ohne ?lang alle Sprachen, sonst eine mit Rueckfall auf Deutsch.
     */
    public static function text(mixed $translations, ?string $lang): mixed
    {
        $translations = is_array($translations) ? array_filter($translations, fn ($value) => is_string($value) && $value !== '') : [];

        if ($lang === null) {
            return $translations ?: (object) [];
        }

        return $translations[$lang] ?? $translations['de'] ?? (reset($translations) ?: null);
    }
}

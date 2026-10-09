<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Airline;
use App\Models\Airport;

/**
 * Fluggesellschaft fuer die Kunden-API: Kurzform fuer Listen, Langform mit Kontakt,
 * Gepaeckregeln je Kabinenklasse, Tierregelung und den angeflogenen Flughaefen.
 */
class AirlineResource
{
    public const PET_RESTRICTIONS = [
        'specific_species' => 'Nur bestimmte Tierarten',
        'breed_restrictions' => 'Rasseeinschränkungen',
        'specific_routes' => 'Nur bestimmte Strecken',
        'temperature_restrictions' => 'Temperaturabhängige Einschränkungen',
        'service_animals_allowed' => 'Assistenztiere erlaubt',
    ];

    /**
     * @return array<string, mixed>
     */
    public static function summary(Airline $airline, ?string $lang): array
    {
        return [
            'iata_code' => $airline->iata_code,
            'icao_code' => $airline->icao_code,
            'name' => trim((string) $airline->name),
            'home_country' => $airline->homeCountry ? [
                'iso_code' => $airline->homeCountry->iso_code,
                'name' => AirportResource::text($airline->homeCountry->name_translations, $lang),
            ] : null,
            'headquarters' => $airline->headquarters,
            'website_url' => $airline->website,
            'booking_url' => $airline->booking_url,
            'cabin_classes' => self::cabinClasses($airline),
            'counts' => [
                'airports' => $airline->relationLoaded('airports') ? $airline->airports->count() : ($airline->airports_count ?? null),
            ],
        ];
    }

    /**
     * @param  array<int, string>  $include  z. B. ['lounges', 'hotels'] – Zusatzdaten je Flughafen
     * @return array<string, mixed>
     */
    public static function detail(Airline $airline, ?string $lang, array $include = []): array
    {
        return array_merge(self::summary($airline, $lang), [
            'contact' => [
                'hotline' => $airline->contact_info['hotline'] ?? null,
                'email' => $airline->contact_info['email'] ?? null,
                'chat_url' => $airline->contact_info['chat_url'] ?? null,
                'help_url' => $airline->contact_info['help_url'] ?? null,
            ],
            'baggage' => self::baggage($airline),
            'pet_policy' => self::petPolicy($airline),
            'airports' => $airline->airports->map(function (Airport $airport) use ($lang, $include) {
                $row = [
                    'iata_code' => $airport->iata_code,
                    'icao_code' => $airport->icao_code,
                    'name' => $airport->name,
                    'city' => $airport->city ? AirportResource::text($airport->city->name_translations, $lang) : null,
                    'country' => $airport->country?->iso_code,
                    'terminal' => $airport->pivot->terminal,
                    'direction' => $airport->pivot->direction,
                ];
                if (in_array('lounges', $include, true)) {
                    $row['lounges'] = AirportResource::lounges($airport);
                }
                if (in_array('hotels', $include, true)) {
                    $row['nearby_hotels'] = AirportResource::hotels($airport);
                }

                return $row;
            })->values()->all(),
            'updated_at' => $airline->updated_at?->toIso8601String(),
        ]);
    }

    /**
     * @return array<int, array{key: string, label: string}>
     */
    public static function cabinClasses(Airline $airline): array
    {
        $labels = Airline::getCabinClassOptions();

        return collect($airline->cabin_classes ?? [])
            ->filter(fn ($key) => is_string($key) && $key !== '')
            ->map(fn (string $key) => ['key' => $key, 'label' => $labels[$key] ?? $key])
            ->values()->all();
    }

    /**
     * Gepaeck je Kabinenklasse: Freitext-Angaben (z. B. "1x7kg") und Handgepaeckmasse in cm.
     *
     * @return array<string, mixed>
     */
    public static function baggage(Airline $airline): array
    {
        $rules = is_array($airline->baggage_rules) ? $airline->baggage_rules : [];
        $classes = [];

        foreach (array_keys(Airline::getCabinClassOptions()) as $class) {
            $dimensions = $rules['hand_baggage_dimensions'][$class] ?? [];
            $dimensions = is_array($dimensions) ? array_filter([
                'length' => $dimensions['length'] ?? null,
                'width' => $dimensions['width'] ?? null,
                'height' => $dimensions['height'] ?? null,
            ], fn ($value) => $value !== null && $value !== '') : [];

            $classes[$class] = [
                'hand' => [
                    'allowance' => self::blank($rules['hand_baggage'][$class] ?? null),
                    'dimensions_cm' => $dimensions !== [] ? array_map('floatval', $dimensions) : null,
                ],
                'checked' => [
                    'allowance' => self::blank($rules['checked_baggage'][$class] ?? null),
                ],
            ];
        }

        return [
            'classes' => $classes,
            'notes' => self::blank($rules['hand_baggage_notes'] ?? null),
            'info_url' => self::blank($rules['hand_baggage_info_url'] ?? null),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function petPolicy(Airline $airline): array
    {
        $pets = is_array($airline->pet_policy) ? $airline->pet_policy : [];
        $part = function (string $key) use ($pets): ?array {
            $p = $pets[$key] ?? null;
            if (! is_array($p)) {
                return null;
            }

            return array_filter([
                'allowed' => (bool) ($p['allowed'] ?? false),
                'max_weight' => self::blank($p['max_weight'] ?? null),
                'carrier_dimensions_cm' => isset($p['carrier_length']) || isset($p['carrier_width']) || isset($p['carrier_height']) ? [
                    'length' => $p['carrier_length'] ?? null,
                    'width' => $p['carrier_width'] ?? null,
                    'height' => $p['carrier_height'] ?? null,
                ] : null,
                'weight_includes_bag' => isset($p['weight_includes_bag']) ? (bool) $p['weight_includes_bag'] : null,
                'advance_notice_required' => isset($p['advance_notice_required']) ? (bool) $p['advance_notice_required'] : null,
                'notes' => self::blank($p['notes'] ?? null),
            ], fn ($value) => $value !== null);
        };

        return [
            'allowed' => (bool) ($pets['allowed'] ?? false),
            'in_cabin' => $part('in_cabin'),
            'in_hold' => $part('in_hold'),
            'restrictions' => collect($pets['restrictions'] ?? [])
                ->filter(fn ($key) => is_string($key))
                ->map(fn (string $key) => ['key' => $key, 'label' => self::PET_RESTRICTIONS[$key] ?? $key])
                ->values()->all(),
            'info_url' => self::blank($pets['info_url'] ?? null),
            'notes' => self::blank($pets['notes'] ?? null),
        ];
    }

    private static function blank(mixed $value): mixed
    {
        return is_string($value) && trim($value) === '' ? null : $value;
    }
}

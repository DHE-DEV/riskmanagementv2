<?php

namespace App\Http\Resources\Api\V1;

use App\Models\City;
use App\Models\Region;

/**
 * Regionen und Staedte eines Landes fuer die Kunden-API.
 * Namen sind mehrsprachig (Objekt je Sprache) oder – mit ?lang – ein String.
 * is_popular entspricht der Markierung "beliebt" in der Verwaltung (Spalte is_major).
 */
class PlaceResource
{
    /**
     * @return array<string, mixed>
     */
    public static function region(Region $region, ?string $lang): array
    {
        return [
            'id' => $region->id,
            'code' => $region->code,
            'name' => AirportResource::text($region->name_translations, $lang),
            // Kurzbeschreibung aus den Regionsinfos (AdminV2), sonst die alte einsprachige Beschreibung.
            'description' => AirportResource::text($region->info['texts']['short_description'] ?? null, $lang ?? 'de')
                ?? (is_string($region->description) && trim($region->description) !== '' ? trim($region->description) : null),
            'is_popular' => (bool) $region->is_major,
            'coordinates' => $region->lat !== null && $region->lng !== null ? ['lat' => (float) $region->lat, 'lng' => (float) $region->lng] : null,
            'cities_count' => (int) ($region->cities_count ?? 0),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function city(City $city, ?string $lang): array
    {
        return [
            'id' => $city->id,
            'name' => AirportResource::text($city->name_translations, $lang),
            'region' => $city->region ? [
                'id' => $city->region->id,
                'code' => $city->region->code,
                'name' => AirportResource::text($city->region->name_translations, $lang),
            ] : null,
            'population' => $city->population !== null ? (int) $city->population : null,
            'coordinates' => $city->lat !== null && $city->lng !== null ? ['lat' => (float) $city->lat, 'lng' => (float) $city->lng] : null,
            'is_capital' => (bool) $city->is_capital,
            'is_regional_capital' => (bool) $city->is_regional_capital,
            'is_popular' => (bool) $city->is_major,
        ];
    }
}

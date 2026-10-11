<?php

namespace App\Http\Resources\Api\V1;

use App\Models\City;
use App\Models\Region;
use App\Models\Subregion;
use App\Support\AdminV2\RegionInfo;
use App\Support\AdminV2\SubregionInfo;

/**
 * Regionen, Teilgebiete (Inseln, Reisegebiete) und Staedte eines Landes fuer die Kunden-API.
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
            'subregions_count' => (int) ($region->subregions_count ?? 0),
        ];
    }

    /**
     * Eine Region mit ihren Infos und Teilgebieten.
     *
     * @return array<string, mixed>
     */
    public static function regionDetail(Region $region, ?string $lang): array
    {
        return self::region($region, $lang) + [
            'country' => self::country($region->country, $lang),
            'info' => self::info($region->info, $lang),
            'subregions' => $region->subregions->map(fn (Subregion $subregion) => self::subregion($subregion, $lang))->values()->all(),
        ];
    }

    /**
     * Teilgebiet fuer Listen: Art, Gruppe fuer Ueberschriften ("Inseln", "Reisegebiete"), Kurzbeschreibung.
     *
     * @return array<string, mixed>
     */
    public static function subregion(Subregion $subregion, ?string $lang): array
    {
        return [
            'id' => $subregion->id,
            'region' => $subregion->region ? [
                'id' => $subregion->region->id,
                'code' => $subregion->region->code,
                'name' => AirportResource::text($subregion->region->name_translations, $lang),
            ] : null,
            'kind' => SubregionInfo::kind($subregion->kind),
            'kind_name' => AirportResource::text(SubregionInfo::kindNames($subregion->kind), $lang),
            'group_name' => AirportResource::text(SubregionInfo::groupNames($subregion->kind), $lang),
            'name' => AirportResource::text($subregion->name_translations, $lang),
            'description' => AirportResource::text($subregion->info['texts']['short_description'] ?? null, $lang ?? 'de'),
            'coordinates' => $subregion->lat !== null && $subregion->lng !== null ? ['lat' => (float) $subregion->lat, 'lng' => (float) $subregion->lng] : null,
            'cities_count' => (int) ($subregion->cities_count ?? $subregion->cities()->count()),
            'sights_count' => (int) ($subregion->sights_count ?? $subregion->sights()->count()),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function subregionDetail(Subregion $subregion, ?string $lang): array
    {
        return self::subregion($subregion, $lang) + [
            'country' => self::country($subregion->country, $lang),
            'info' => self::info($subregion->info, $lang),
        ];
    }

    /**
     * Regions- bzw. Teilgebietsinfos: Texte (mit "lang" als String, sonst je
     * Sprache), "known_for" als Liste, dazu Reisemonate und Zahlen.
     *
     * @return array<string, mixed>
     */
    public static function info(?array $info, ?string $lang): array
    {
        $result = [];

        foreach (RegionInfo::allTexts() as $field => [, $kind]) {
            $values = (array) ($info['texts'][$field] ?? []);

            if ($kind === 'tags') {
                $values = array_filter($values, fn ($tags) => is_array($tags) && $tags !== []);
                $result[$field] = $lang === null ? ($values ?: null) : (array_values($values[$lang] ?? $values['de'] ?? (reset($values) ?: [])) ?: null);
            } else {
                $value = AirportResource::text($values, $lang);
                $result[$field] = $lang === null ? ((array) $value ?: null) : $value;
            }
        }

        return $result + [
            'best_months' => RegionInfo::months((array) ($info['best_months'] ?? [])) ?: null,
            'population' => isset($info['population']) ? (int) $info['population'] : null,
            'area_km2' => isset($info['area_km2']) ? (int) $info['area_km2'] : null,
            'timezone' => $info['timezone'] ?? null,
        ];
    }

    /**
     * @return array{code: string, name: mixed}|null
     */
    public static function country(?\App\Models\Country $country, ?string $lang): ?array
    {
        return $country ? ['code' => $country->iso_code, 'name' => AirportResource::text($country->name_translations, $lang)] : null;
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
            'subregion' => $city->subregion ? [
                'id' => $city->subregion->id,
                'name' => AirportResource::text($city->subregion->name_translations, $lang),
            ] : null,
            'population' => $city->population !== null ? (int) $city->population : null,
            'coordinates' => $city->lat !== null && $city->lng !== null ? ['lat' => (float) $city->lat, 'lng' => (float) $city->lng] : null,
            'is_capital' => (bool) $city->is_capital,
            'is_regional_capital' => (bool) $city->is_regional_capital,
            'is_popular' => (bool) $city->is_major,
        ];
    }
}

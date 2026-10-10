<?php

namespace App\Support\AdminV2;

use App\Models\City;
use App\Models\Country;
use App\Models\Region;
use App\Models\Sight;

/**
 * Sehenswuerdigkeiten als JSON-Datei ausgeben und einlesen – so lassen sie
 * sich einmal (z. B. lokal per KI) erzeugen, im Repository ablegen und per
 * Migration auf jedem System anlegen, ohne dort die KI zu befragen.
 *
 * Aufbau: {"IT": {"Ligurien": [{name_translations, category, is_highlight, sort_order,
 *          city, lat, lng, address, website_url, ticket_url, info}, …]}, …}
 * Land ueber den ISO-Code, Region und Stadt ueber den deutschen Namen.
 */
class SightTransfer
{
    /**
     * @param  array<int, string>  $isoCodes
     * @return array<string, array<string, array<int, array<string, mixed>>>>
     */
    public static function export(array $isoCodes): array
    {
        $data = [];

        foreach (Country::query()->whereIn('iso_code', array_map('strtoupper', $isoCodes))->orderBy('iso_code')->get() as $country) {
            $sights = Sight::query()->where('country_id', $country->id)->with(['region', 'city'])->orderBy('region_id')->orderBy('sort_order')->orderBy('id')->get();

            foreach ($sights as $sight) {
                $data[$country->iso_code][$sight->region?->getName('de') ?? ''][] = [
                    'name_translations' => $sight->name_translations,
                    'category' => $sight->category,
                    'is_highlight' => (bool) $sight->is_highlight,
                    'sort_order' => (int) $sight->sort_order,
                    'city' => $sight->city?->getName('de'),
                    'lat' => $sight->lat !== null ? (float) $sight->lat : null,
                    'lng' => $sight->lng !== null ? (float) $sight->lng : null,
                    'address' => $sight->address,
                    'website_url' => $sight->website_url,
                    'ticket_url' => $sight->ticket_url,
                    'info' => $sight->info,
                ];
            }
        }

        return $data;
    }

    /**
     * Einlesen. Uebersprungen wird, was es im Land schon gibt (gleicher Name)
     * oder wozu Land bzw. Region fehlen.
     *
     * @param  array<string, array<string, array<int, array<string, mixed>>>>  $data
     * @return array{created: int, skipped: int, missing_regions: array<int, string>}
     */
    public static function import(array $data): array
    {
        $created = 0;
        $skipped = 0;
        $missing = [];

        foreach ($data as $iso => $regions) {
            $country = Country::query()->where('iso_code', strtoupper((string) $iso))->first();

            if (! $country) {
                $missing[] = (string) $iso;
                $skipped += collect($regions)->flatten(1)->count();

                continue;
            }

            $regionByName = Region::query()->where('country_id', $country->id)->get()->keyBy(fn (Region $region) => SightInfo::normalizeName($region->getName('de')));
            $cities = City::query()->where('country_id', $country->id)->get();
            $existing = Sight::withTrashed()->where('country_id', $country->id)->get()
                ->flatMap(fn (Sight $sight) => array_map(fn ($name) => SightInfo::normalizeName((string) $name), array_values((array) $sight->name_translations)))
                ->filter()->flip();

            foreach ($regions as $regionName => $sights) {
                $region = $regionName === '' ? null : $regionByName->get(SightInfo::normalizeName((string) $regionName));

                if ($regionName !== '' && ! $region) {
                    $missing[] = $iso.': '.$regionName;
                    $skipped += count($sights);

                    continue;
                }

                foreach ($sights as $item) {
                    $names = array_filter(array_map(fn ($name) => SightInfo::normalizeName((string) $name), (array) ($item['name_translations'] ?? [])));

                    if ($names === [] || collect($names)->contains(fn ($name) => $existing->has($name))) {
                        $skipped++;

                        continue;
                    }

                    $cityName = SightInfo::normalizeName((string) ($item['city'] ?? ''));
                    $city = $cityName === '' ? null : ($cities->first(fn (City $city) => $city->region_id === $region?->id && SightInfo::normalizeName($city->getName('de')) === $cityName)
                        ?? $cities->first(fn (City $city) => SightInfo::normalizeName($city->getName('de')) === $cityName));

                    Sight::create([
                        'name_translations' => $item['name_translations'],
                        'country_id' => $country->id,
                        'region_id' => $region?->id,
                        'city_id' => $city?->id,
                        'category' => isset(SightInfo::CATEGORIES[$item['category'] ?? '']) ? $item['category'] : 'other',
                        'is_highlight' => (bool) ($item['is_highlight'] ?? false),
                        'sort_order' => (int) ($item['sort_order'] ?? 0),
                        'lat' => $item['lat'] ?? null,
                        'lng' => $item['lng'] ?? null,
                        'address' => $item['address'] ?? null,
                        'website_url' => $item['website_url'] ?? null,
                        'ticket_url' => $item['ticket_url'] ?? null,
                        'info' => $item['info'] ?? null,
                    ]);

                    foreach ($names as $name) {
                        $existing->put($name, true);
                    }
                    $created++;
                }
            }
        }

        return ['created' => $created, 'skipped' => $skipped, 'missing_regions' => array_values(array_unique($missing))];
    }
}

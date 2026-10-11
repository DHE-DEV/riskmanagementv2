<?php

namespace App\Support\AdminV2;

use App\Models\City;
use App\Models\Country;
use App\Models\Region;
use App\Models\Sight;
use App\Models\Subregion;

/**
 * Teilgebiete (Inseln, Reisegebiete) als JSON-Datei ausgeben und einlesen –
 * wie SightTransfer: lokal erzeugen, im Repository ablegen, per Migration
 * auf jedem System anlegen.
 *
 * Aufbau: {"ES": {"Kanarische Inseln": [{kind, name_translations, code, lat, lng,
 *          sort_order, info, cities: ["Santa Cruz de Tenerife"], sights: [{de: "Nationalpark Teide", en: …}]}, …]}, …}
 * Land ueber den ISO-Code, Region und Staedte ueber den deutschen Namen,
 * Sehenswuerdigkeiten ueber einen ihrer Namen (auf einem anderen System kann
 * der deutsche abweichen). Sehenswuerdigkeiten einer Nachbarregion (der Gardasee
 * liegt in drei Regionen) werden im ganzen Land gesucht.
 */
class SubregionTransfer
{
    /**
     * @param  array<int, string>  $isoCodes
     * @return array<string, array<string, array<int, array<string, mixed>>>>
     */
    public static function export(array $isoCodes): array
    {
        $data = [];

        foreach (Country::query()->whereIn('iso_code', array_map('strtoupper', $isoCodes))->orderBy('iso_code')->get() as $country) {
            $subregions = Subregion::query()->where('country_id', $country->id)->with(['region', 'cities', 'sights'])->orderBy('region_id')->orderBy('sort_order')->orderBy('id')->get();

            foreach ($subregions as $subregion) {
                $data[$country->iso_code][$subregion->region?->getName('de') ?? ''][] = [
                    'kind' => $subregion->kind,
                    'name_translations' => $subregion->name_translations,
                    'code' => $subregion->code,
                    'lat' => $subregion->lat !== null ? (float) $subregion->lat : null,
                    'lng' => $subregion->lng !== null ? (float) $subregion->lng : null,
                    'sort_order' => (int) $subregion->sort_order,
                    'info' => $subregion->info,
                    'cities' => $subregion->cities->map(fn (City $city) => $city->getName('de'))->sort()->values()->all(),
                    'sights' => $subregion->sights->sortBy(fn (Sight $sight) => $sight->getName('de'))->map(fn (Sight $sight) => $sight->name_translations)->values()->all(),
                ];
            }
        }

        return $data;
    }

    /**
     * Einlesen. Ein Teilgebiet, das es in der Region schon gibt (gleicher
     * Name), wird nicht doppelt angelegt; zugeordnet wird nur, was noch
     * keinem Teilgebiet angehoert.
     *
     * @param  array<string, array<string, array<int, array<string, mixed>>>>  $data
     * @return array{created: int, skipped: int, cities: int, sights: int, missing: array<int, string>}
     */
    public static function import(array $data): array
    {
        $result = ['created' => 0, 'skipped' => 0, 'cities' => 0, 'sights' => 0, 'missing' => []];

        foreach ($data as $iso => $regions) {
            $country = Country::query()->where('iso_code', strtoupper((string) $iso))->first();

            if (! $country) {
                $result['missing'][] = (string) $iso;

                continue;
            }

            $regionByName = Region::query()->where('country_id', $country->id)->get()->keyBy(fn (Region $region) => SightInfo::normalizeName($region->getName('de')));
            $cities = City::query()->where('country_id', $country->id)->get();
            $sights = Sight::query()->where('country_id', $country->id)->get();

            foreach ($regions as $regionName => $items) {
                $region = $regionByName->get(SightInfo::normalizeName((string) $regionName));

                if (! $region) {
                    $result['missing'][] = $iso.': '.$regionName;
                    $result['skipped'] += count($items);

                    continue;
                }

                $existing = Subregion::withTrashed()->where('region_id', $region->id)->get()
                    ->keyBy(fn (Subregion $subregion) => SightInfo::normalizeName($subregion->getName('de')));

                foreach ($items as $item) {
                    $name = SightInfo::normalizeName((string) ($item['name_translations']['de'] ?? ''));

                    if ($name === '') {
                        $result['skipped']++;

                        continue;
                    }

                    $subregion = $existing->get($name);

                    if ($subregion) {
                        $result['skipped']++;
                    } else {
                        $subregion = Subregion::create([
                            'region_id' => $region->id,
                            'country_id' => $country->id,
                            'kind' => SubregionInfo::kind($item['kind'] ?? null),
                            'name_translations' => $item['name_translations'],
                            'code' => $item['code'] ?? null,
                            'lat' => $item['lat'] ?? null,
                            'lng' => $item['lng'] ?? null,
                            'sort_order' => (int) ($item['sort_order'] ?? 0),
                            'info' => $item['info'] ?? null,
                        ]);
                        $existing->put($name, $subregion);
                        $result['created']++;
                    }

                    foreach ((array) ($item['cities'] ?? []) as $cityName) {
                        $wanted = SightInfo::normalizeName((string) $cityName);
                        $city = $cities->first(fn (City $city) => $city->region_id === $region->id && SightInfo::normalizeName($city->getName('de')) === $wanted);

                        if ($city && ! $city->subregion_id) {
                            $city->forceFill(['subregion_id' => $subregion->id])->save();
                            $result['cities']++;
                        }
                    }

                    foreach ((array) ($item['sights'] ?? []) as $sightNames) {
                        $wanted = array_filter(array_map(fn ($name) => SightInfo::normalizeName((string) $name), (array) $sightNames));
                        $matches = $sights->filter(fn (Sight $sight) => collect((array) $sight->name_translations)->contains(fn ($value) => in_array(SightInfo::normalizeName((string) $value), $wanted, true)));
                        $sight = $matches->firstWhere('region_id', $region->id) ?? $matches->first();

                        if ($sight && ! $sight->subregion_id) {
                            $sight->forceFill(['subregion_id' => $subregion->id])->save();
                            $result['sights']++;
                        }
                    }
                }
            }
        }

        $result['missing'] = array_values(array_unique($result['missing']));

        return $result;
    }
}

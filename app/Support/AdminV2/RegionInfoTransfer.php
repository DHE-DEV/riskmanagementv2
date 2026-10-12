<?php

namespace App\Support\AdminV2;

use App\Models\Country;
use App\Models\Region;

/**
 * Regionsinfos und Teilgebiete (samt Infos und Zuordnung) als JSON-Datei
 * ausgeben und einlesen – lokal mit regions:fill-missing vervollstaendigen,
 * im Repository ablegen, per Migration auf jedem System einspielen.
 *
 * Aufbau: {"ES": {"regions": {"Katalonien": {"info": {…}, "subregions_checked": true}, …},
 *                 "subregions": {<wie SubregionTransfer>}}, …}
 * Regionen ueber den deutschen Namen. Beim Einlesen werden nur Luecken
 * gefuellt: vorhandene Texte, Pruefangaben und Teilgebiete bleiben.
 */
class RegionInfoTransfer
{
    /**
     * @param  array<int, string>  $isoCodes
     * @return array<string, array{regions: array<string, array<string, mixed>>, subregions: array<string, array<int, array<string, mixed>>>}>
     */
    public static function export(array $isoCodes): array
    {
        $data = [];
        $subregions = SubregionTransfer::export($isoCodes);

        foreach (Country::query()->whereIn('iso_code', array_map('strtoupper', $isoCodes))->orderBy('iso_code')->get() as $country) {
            $regions = Region::query()->where('country_id', $country->id)->orderBy('id')->get()
                ->sortBy(fn (Region $region) => $region->getName('de'))
                ->mapWithKeys(fn (Region $region) => [$region->getName('de') => array_filter([
                    'info' => $region->info,
                    'subregions_checked' => $region->subregions_checked_at !== null,
                ])])
                ->all();

            $data[$country->iso_code] = ['regions' => $regions, 'subregions' => $subregions[$country->iso_code] ?? []];
        }

        return $data;
    }

    /**
     * @param  array<string, array<string, mixed>>  $data
     * @return array{regions: int, checked: int, subregions: array<string, mixed>, missing: array<int, string>}
     */
    public static function import(array $data): array
    {
        $result = ['regions' => 0, 'checked' => 0, 'subregions' => [], 'missing' => []];
        $subregions = [];

        foreach ($data as $iso => $item) {
            $country = Country::query()->where('iso_code', strtoupper((string) $iso))->first();

            if (! $country) {
                $result['missing'][] = (string) $iso;

                continue;
            }

            $regionByName = Region::query()->where('country_id', $country->id)->get()->keyBy(fn (Region $region) => SightInfo::normalizeName($region->getName('de')));

            foreach ((array) ($item['regions'] ?? []) as $name => $values) {
                $region = $regionByName->get(SightInfo::normalizeName((string) $name));

                if (! $region) {
                    $result['missing'][] = $iso.': '.$name;

                    continue;
                }

                $info = is_array($values['info'] ?? null)
                    ? RegionInfo::applyAi($region->info, $values['info'], (string) ($values['info']['meta']['ai_model'] ?? ''), false, $country->timezone, $values['info']['meta']['ai_generated_at'] ?? null)
                    : null;

                if ($info !== null) {
                    $region->forceFill(['info' => $info]);
                    $result['regions']++;
                }

                if (($values['subregions_checked'] ?? false) && ! $region->subregions_checked_at) {
                    $region->forceFill(['subregions_checked_at' => now()]);
                    $result['checked']++;
                }

                $region->save();
            }

            $subregions[$iso] = (array) ($item['subregions'] ?? []);
        }

        // Nach den Regionen: Teilgebiete anlegen bzw. ihre Luecken fuellen, Staedte und Sehenswuerdigkeiten zuordnen.
        $result['subregions'] = SubregionTransfer::import($subregions, mergeInfo: true);
        $result['missing'] = array_values(array_unique([...$result['missing'], ...$result['subregions']['missing']]));

        return $result;
    }
}

<?php

namespace App\Services;

use App\Models\City;
use App\Models\Country;
use App\Models\CustomEvent;
use App\Models\Region;
use Illuminate\Support\Facades\DB;

/**
 * Standort-Datensaetze eines Ereignisses (Tabelle country_custom_event).
 *
 * Ein Standort ist immer ein Land, optional verfeinert um Region und Stadt.
 * Pro Land sind beliebig viele Datensaetze erlaubt – deshalb wird beim
 * Speichern zeilenweise geschrieben statt ueber sync().
 */
class CustomEventLocationService
{
    /**
     * Eine Suche ueber Laender, Regionen und Staedte.
     *
     * Region und Land stehen im Kontext, damit vor der Auswahl erkennbar ist,
     * welcher Ort gemeint ist – Ortsnamen sind selten eindeutig.
     *
     * @return array<int, array{type: string, id: int, name: string, context: string}>
     */
    public function search(string $term, int $limitPerType = 6): array
    {
        $term = trim($term);

        if (mb_strlen($term) < 2) {
            return [];
        }

        $needle = mb_strtolower($term);
        $like = '%'.$needle.'%';

        // Beste Treffer zuerst: exakter Name, dann Wortanfang, dann der Rest –
        // sonst verdraengt "Bromberg" bei der Suche nach "Rom" die Stadt Rom.
        $nameSql = "LOWER(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(name_translations, '$.de')), JSON_UNQUOTE(JSON_EXTRACT(name_translations, '$.en'))))";
        $byRelevance = fn ($query) => $query
            ->orderByRaw("CASE WHEN {$nameSql} = ? THEN 0 WHEN {$nameSql} LIKE ? THEN 1 ELSE 2 END", [$needle, $needle.'%'])
            ->orderByRaw($nameSql);
        $rank = fn (string $name) => match (true) {
            mb_strtolower($name) === $needle => 0,
            str_starts_with(mb_strtolower($name), $needle) => 1,
            default => 2,
        };

        // Ueber JSON_EXTRACT statt LIKE auf der Spalte: MySQL vergleicht eine
        // JSON-Spalte binaer, die Suche waere sonst case-sensitiv. Englisch als
        // Rueckfall – nicht jeder Ort hat eine deutsche Uebersetzung.
        $byName = function ($query) use ($like) {
            $query->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name_translations, '$.de'))) LIKE ?", [$like])
                ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name_translations, '$.en'))) LIKE ?", [$like]);
        };

        $countries = Country::query()
            ->where(function ($query) use ($byName, $term) {
                $byName($query);
                $query->orWhere('iso_code', $term)->orWhere('iso3_code', $term);
            })
            ->orderByRaw('CASE WHEN iso_code = ? OR iso3_code = ? THEN 0 ELSE 1 END', [$term, $term])
            ->tap($byRelevance)
            ->limit($limitPerType)
            ->get()
            ->map(fn (Country $country) => [
                'type' => 'country',
                'id' => $country->id,
                'name' => $country->getName('de'),
                'context' => $country->iso_code,
            ]);

        $regions = Region::query()
            ->with('country')
            ->where(function ($query) use ($byName, $term) {
                $byName($query);
                $query->orWhere('code', 'like', '%'.$term.'%');
            })
            ->tap($byRelevance)
            ->limit($limitPerType)
            ->get()
            ->map(fn (Region $region) => [
                'type' => 'region',
                'id' => $region->id,
                'name' => $region->getName('de'),
                'context' => (string) $region->country?->getName('de'),
            ]);

        $cities = City::query()
            ->with(['country', 'region'])
            ->where($byName)
            ->tap($byRelevance)
            ->limit($limitPerType)
            ->get()
            ->map(fn (City $city) => [
                'type' => 'city',
                'id' => $city->id,
                'name' => $city->getName('de'),
                'context' => implode(' – ', array_filter([
                    $city->region?->getName('de'),
                    $city->country?->getName('de'),
                ])),
            ]);

        // Stabil sortiert: bei gleichem Rang bleibt die Reihenfolge Land, Region, Stadt.
        return $countries->concat($regions)->concat($cities)
            ->sortBy(fn (array $result) => $result['type'] === 'country' && mb_strtolower($result['context']) === $needle
                ? 0
                : $rank($result['name']))
            ->values()
            ->all();
    }

    /**
     * Einen Suchtreffer in einen Standort-Datensatz uebersetzen.
     *
     * @return array{country_id: int, region_id: ?int, city_id: ?int}|null
     */
    public function resolve(string $type, int $id): ?array
    {
        return match ($type) {
            'country' => Country::whereKey($id)->exists()
                ? ['country_id' => $id, 'region_id' => null, 'city_id' => null]
                : null,
            'region' => ($region = Region::find($id))
                ? ['country_id' => (int) $region->country_id, 'region_id' => $region->id, 'city_id' => null]
                : null,
            'city' => ($city = City::find($id))
                ? [
                    'country_id' => (int) $city->country_id,
                    'region_id' => $city->region_id ? (int) $city->region_id : null,
                    'city_id' => $city->id,
                ]
                : null,
            default => null,
        };
    }

    /**
     * Anzeigename eines Standorts, z. B. "Spanien – Katalonien – Barcelona".
     */
    public function label(?int $countryId, ?int $regionId = null, ?int $cityId = null): string
    {
        return implode(' – ', array_filter([
            $countryId ? Country::find($countryId)?->getName('de') : null,
            $regionId ? Region::find($regionId)?->getName('de') : null,
            $cityId ? City::find($cityId)?->getName('de') : null,
        ]));
    }

    /**
     * Standard-Koordinaten nach der Kaskade Stadt > Region > Hauptstadt > Land.
     *
     * @return array{0: float, 1: float}|null
     */
    public function defaultCoordinatesFor(?int $countryId, ?int $regionId = null, ?int $cityId = null): ?array
    {
        if ($cityId && ($city = City::find($cityId)) && $city->lat && $city->lng) {
            return [(float) $city->lat, (float) $city->lng];
        }

        if ($regionId && ($region = Region::find($regionId)) && $region->lat && $region->lng) {
            return [(float) $region->lat, (float) $region->lng];
        }

        if ($countryId && ($country = Country::with('capital')->find($countryId))) {
            if ($country->capital && $country->capital->lat && $country->capital->lng) {
                return [(float) $country->capital->lat, (float) $country->capital->lng];
            }

            if ($country->lat && $country->lng) {
                return [(float) $country->lat, (float) $country->lng];
            }
        }

        return null;
    }

    /**
     * Koordinaten aus den ueblichen Schreibweisen lesen, wie sie aus Google
     * Maps kopiert werden: "50.1109, 8.6821", "@50.1109,8.6821" oder
     * 50°06'39.2"N 8°40'55.6"E.
     *
     * @return array{0: float, 1: float}|null
     */
    public function parseCoordinates(?string $input): ?array
    {
        $input = trim((string) $input);

        if ($input === '') {
            return null;
        }

        $coordinates = null;

        if (preg_match('/(\d+)°\s*(\d+)[\'′]\s*([\d.]+)["″]\s*([NS])[\s,]+(\d+)°\s*(\d+)[\'′]\s*([\d.]+)["″]\s*([EW])/u', $input, $m)) {
            $lat = (float) $m[1] + (float) $m[2] / 60 + (float) $m[3] / 3600;
            $lng = (float) $m[5] + (float) $m[6] / 60 + (float) $m[7] / 3600;
            $coordinates = [$m[4] === 'S' ? -$lat : $lat, $m[8] === 'W' ? -$lng : $lng];
        } elseif (preg_match('/(-?\d+(?:\.\d+)?)\s*°?\s*([NS])?\s*[,;\s]\s*(-?\d+(?:\.\d+)?)\s*°?\s*([EW])?/u', ltrim($input, '@'), $m)) {
            $lat = (float) $m[1];
            $lng = (float) $m[3];
            $coordinates = [
                ($m[2] ?? '') === 'S' ? -$lat : $lat,
                ($m[4] ?? '') === 'W' ? -$lng : $lng,
            ];
        }

        if (! $coordinates || abs($coordinates[0]) > 90 || abs($coordinates[1]) > 180) {
            return null;
        }

        return $coordinates;
    }

    /**
     * Standort-Datensaetze des Ereignisses im Bearbeitungsformat.
     *
     * @return array<int, array<string, mixed>>
     */
    public function rowsFor(CustomEvent $event): array
    {
        return collect($event->locationRecords('de'))
            ->map(fn (array $record) => [
                'country_id' => $record['country_id'],
                'region_id' => $record['region_id'],
                'city_id' => $record['city_id'],
                'label' => $record['label'],
                'iso_code' => $record['iso_code'],
                'use_default_coordinates' => $record['use_default_coordinates'],
                'coordinates' => $record['latitude'] !== null && $record['longitude'] !== null
                    ? $record['latitude'].', '.$record['longitude']
                    : '',
                'location_note' => (string) $record['location_note'],
            ])
            ->all();
    }

    /**
     * Alle Standort-Datensaetze des Ereignisses durch die uebergebenen ersetzen.
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function replace(CustomEvent $event, array $rows): void
    {
        $now = now();
        $inserts = [];

        foreach ($rows as $row) {
            if (empty($row['country_id'])) {
                continue;
            }

            $useDefault = (bool) ($row['use_default_coordinates'] ?? true);
            $countryId = (int) $row['country_id'];
            $regionId = ! empty($row['region_id']) ? (int) $row['region_id'] : null;
            $cityId = ! empty($row['city_id']) ? (int) $row['city_id'] : null;

            $coordinates = $useDefault
                ? $this->defaultCoordinatesFor($countryId, $regionId, $cityId)
                : $this->parseCoordinates($row['coordinates'] ?? null);

            $inserts[] = [
                'custom_event_id' => $event->getKey(),
                'country_id' => $countryId,
                'region_id' => $regionId,
                'city_id' => $cityId,
                'latitude' => $coordinates[0] ?? null,
                'longitude' => $coordinates[1] ?? null,
                'location_note' => filled($row['location_note'] ?? null) ? $row['location_note'] : null,
                'use_default_coordinates' => $useDefault,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::transaction(function () use ($event, $inserts) {
            DB::table('country_custom_event')
                ->where('custom_event_id', $event->getKey())
                ->delete();

            if ($inserts !== []) {
                DB::table('country_custom_event')->insert($inserts);
            }
        });

        $event->unsetRelation('countries');
    }
}

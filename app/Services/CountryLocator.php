<?php

namespace App\Services;

use App\Models\AirportCode;
use App\Models\Country;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ordnet einen Abfragepunkt (Koordinaten oder 3-Letter-Code) einem Land zu.
 *
 * Wird fuer landesweite Ereignisse gebraucht: die gelten im gesamten Land,
 * also muss zu einer Koordinate bzw. einem Flughafencode das Land bestimmt werden.
 *
 * Bewusst ohne externen Dienst (kein Nominatim/Google) - die Aufloesung laeuft in
 * Such-Endpunkten und darf weder Latenz noch Rate-Limits einschleppen.
 *
 * Kaskade fuer Koordinaten:
 *   1. Landesgrenze (country_boundaries, Natural Earth) per ST_Contains - exakt
 *   2. Naechste Landesgrenze innerhalb einer Toleranz - faengt Kuesten-, Hafen- und
 *      Offshore-Punkte ab, die bei 1:10 Mio. Aufloesung knapp neben dem Polygon liegen
 *   3. Naechster Flughafen / naechste Stadt / Landes-Mittelpunkt - greift nur noch,
 *      solange fuer ein Land keine Grenze importiert ist (siehe countries:import-boundaries)
 */
class CountryLocator
{
    /**
     * Toleranz, bis zu der ein Punkt ausserhalb eines Landespolygons noch diesem Land
     * zugerechnet wird (km). Deckt Kuestenorte, Haefen und kuestennahe Schiffspositionen ab.
     */
    private const BOUNDARY_TOLERANCE_KM = 25;

    /** Maximale Entfernung, bis zu der ein Flughafen als Landesnachweis gilt (km). */
    private const AIRPORT_MAX_DISTANCE_KM = 500;

    /** Maximale Entfernung, bis zu der eine Stadt als Landesnachweis gilt (km). */
    private const CITY_MAX_DISTANCE_KM = 500;

    /** @var array<string, ?int> Prozess-Cache: "lat,lng" bzw. "iata:XXX" => country_id */
    private array $cache = [];

    /** Einmal je Request pruefen, ob Grenzdaten vorliegen. */
    private static ?bool $boundariesAvailable = null;

    /** @var ?array<int, int> Laender mit hinterlegter Grenze, als Lookup-Map */
    private static ?array $countriesWithBoundary = null;

    /**
     * Land zu einem 3-Letter-Code (IATA). Exakt, da airport_codes_1.iso_country
     * lueckenlos gepflegt ist - keine Geo-Naeherung noetig.
     */
    public function countryIdForAirportCode(string $code): ?int
    {
        $code = strtoupper(trim($code));

        if (strlen($code) !== 3) {
            return null;
        }

        return $this->cache['iata:' . $code] ??= (function () use ($code): ?int {
            $airport = AirportCode::query()
                ->where('iata_code', $code)
                ->first(['country_id', 'iso_country', 'latitude_deg', 'longitude_deg']);

            if (! $airport) {
                return null;
            }

            // iso_country ist fuehrend: die Spalte ist lueckenlos gepflegt, waehrend
            // country_id nur bei ~0,1% der Zeilen gesetzt und dort teils falsch ist
            // (z.B. BCN/Barcelona zeigte auf Andorra).
            if ($airport->iso_country && $countryId = $this->countryIdForIso($airport->iso_country)) {
                return $countryId;
            }

            // Letzter Ausweg: ueber die Koordinaten des Flughafens.
            if ($airport->latitude_deg && $airport->longitude_deg) {
                return $this->countryIdForCoordinates(
                    (float) $airport->latitude_deg,
                    (float) $airport->longitude_deg,
                );
            }

            return null;
        })();
    }

    /**
     * Land zu einem Koordinatenpaar. Kaskade: naechster Flughafen > naechste Stadt > Landes-Mittelpunkt.
     */
    public function countryIdForCoordinates(float $latitude, float $longitude): ?int
    {
        $key = round($latitude, 4) . ',' . round($longitude, 4);

        return $this->cache[$key] ??= $this->resolveCoordinates($latitude, $longitude);
    }

    private function resolveCoordinates(float $latitude, float $longitude): ?int
    {
        $exact = $this->boundaryCountryId($latitude, $longitude)
            ?? $this->nearestBoundaryCountryId($latitude, $longitude);

        if ($exact !== null) {
            return $exact;
        }

        $approximate = $this->nearestAirportCountryId($latitude, $longitude)
            ?? $this->nearestCityCountryId($latitude, $longitude)
            ?? $this->nearestCountryCentroidId($latitude, $longitude);

        // Liegt fuer das genaeherte Land eine Grenze vor, ist sie massgeblich: sie hat
        // den Punkt oben nicht enthalten, also liegt er nicht in diesem Land. Ohne diese
        // Pruefung wuerde z.B. ein Punkt mitten im Ozean ueber den naechsten Flughafen
        // faelschlich einem Land zugeordnet. Die Naeherung bleibt damit genau das, was
        // sie sein soll - eine Luecke fuer die Laender ohne Grenzdaten.
        if ($approximate !== null && $this->countryHasBoundary($approximate)) {
            return null;
        }

        return $approximate;
    }

    private function countryHasBoundary(int $countryId): bool
    {
        if (! $this->boundariesAvailable()) {
            return false;
        }

        static::$countriesWithBoundary ??= DB::table('country_boundaries')
            ->pluck('country_id')
            ->map(fn ($id) => (int) $id)
            ->flip()
            ->all();

        return isset(static::$countriesWithBoundary[$countryId]);
    }

    /**
     * Sind Landesgrenzen importiert? Ohne Import faellt alles auf die Naeherung zurueck,
     * damit die Suche auch vor dem ersten countries:import-boundaries funktioniert.
     */
    private function boundariesAvailable(): bool
    {
        return static::$boundariesAvailable ??= Schema::hasTable('country_boundaries')
            && DB::table('country_boundaries')->exists();
    }

    /**
     * Exakter Treffer: Punkt liegt im Polygon des Landes.
     *
     * Achtung Achsenreihenfolge: ST_GeomFromGeoJSON hat die Geometrien in der
     * GeoJSON-Reihenfolge abgelegt, Abfragen brauchen deshalb POINT(lng, lat).
     */
    private function boundaryCountryId(float $latitude, float $longitude): ?int
    {
        if (! $this->boundariesAvailable()) {
            return null;
        }

        $row = DB::selectOne(
            'select country_id from country_boundaries
             where ST_Contains(boundary, ST_SRID(POINT(?, ?), 4326))
             limit 1',
            [$longitude, $latitude],
        );

        return $row ? (int) $row->country_id : null;
    }

    /**
     * Naechstgelegene Landesgrenze innerhalb der Toleranz.
     *
     * Kuestenorte, Haefen und Schiffspositionen liegen bei 1:10 Mio. Aufloesung oft
     * knapp ausserhalb des Polygons (Nuuk z.B. rund 0,6 km). Der Bounding-Box-Vorfilter
     * begrenzt die teure Distanzrechnung auf wenige Kandidaten.
     */
    private function nearestBoundaryCountryId(float $latitude, float $longitude): ?int
    {
        if (! $this->boundariesAvailable()) {
            return null;
        }

        $latDelta = self::BOUNDARY_TOLERANCE_KM / 111.0;
        $lngDelta = self::BOUNDARY_TOLERANCE_KM / max(1.0, 111.0 * cos(deg2rad($latitude)));

        // Bewusst ohne ORDER BY in SQL: MySQL muesste dafuer ueber die sehr grossen
        // Polygone sortieren und laeuft in "Out of sort memory". Die Bounding-Box
        // laesst nur eine Handvoll Kandidaten uebrig - die Auswahl macht PHP.
        $rows = DB::select(
            'select country_id,
                    ST_Distance(boundary, ST_SRID(POINT(?, ?), 4326)) as distance_m
             from country_boundaries
             where min_lat <= ? and max_lat >= ?
               and min_lng <= ? and max_lng >= ?',
            [
                $longitude, $latitude,
                $latitude + $latDelta, $latitude - $latDelta,
                $longitude + $lngDelta, $longitude - $lngDelta,
            ],
        );

        $toleranceMeters = self::BOUNDARY_TOLERANCE_KM * 1000;
        $best = null;

        foreach ($rows as $row) {
            if ($row->distance_m === null || $row->distance_m > $toleranceMeters) {
                continue;
            }

            if ($best === null || $row->distance_m < $best->distance_m) {
                $best = $row;
            }
        }

        return $best ? (int) $best->country_id : null;
    }

    /**
     * Naechstgelegener Flughafen. Vorfilter ueber ein Breiten-/Laengengrad-Fenster,
     * damit MySQL nicht fuer jede Zeile die Haversine-Formel rechnen muss.
     */
    private function nearestAirportCountryId(float $latitude, float $longitude): ?int
    {
        $row = $this->nearestByHaversine(
            table: 'airport_codes_1',
            latColumn: 'latitude_deg',
            lngColumn: 'longitude_deg',
            select: 'iso_country, country_id',
            latitude: $latitude,
            longitude: $longitude,
            maxDistanceKm: self::AIRPORT_MAX_DISTANCE_KM,
            extraWhere: 'deleted_at is null',
        );

        if (! $row) {
            return null;
        }

        // Siehe countryIdForAirportCode(): iso_country ist die verlaessliche Spalte.
        return ! empty($row->iso_country)
            ? $this->countryIdForIso($row->iso_country)
            : null;
    }

    /**
     * ISO-2-Code zu country_id, mit Prozess-Cache.
     */
    private function countryIdForIso(string $isoCode): ?int
    {
        $isoCode = strtoupper(trim($isoCode));

        return $this->cache['iso:' . $isoCode] ??= (function () use ($isoCode): ?int {
            $id = Country::where('iso_code', $isoCode)->value('id');

            return $id ? (int) $id : null;
        })();
    }

    private function nearestCityCountryId(float $latitude, float $longitude): ?int
    {
        $row = $this->nearestByHaversine(
            table: 'cities',
            latColumn: 'lat',
            lngColumn: 'lng',
            select: 'country_id',
            latitude: $latitude,
            longitude: $longitude,
            maxDistanceKm: self::CITY_MAX_DISTANCE_KM,
            extraWhere: 'country_id is not null',
        );

        return $row && $row->country_id ? (int) $row->country_id : null;
    }

    private function nearestCountryCentroidId(float $latitude, float $longitude): ?int
    {
        $row = $this->nearestByHaversine(
            table: 'countries',
            latColumn: 'lat',
            lngColumn: 'lng',
            select: 'id as country_id',
            latitude: $latitude,
            longitude: $longitude,
            // Landes-Mittelpunkte liegen weit auseinander - hier kein enges Limit.
            maxDistanceKm: 5000,
            extraWhere: 'deleted_at is null',
        );

        return $row && $row->country_id ? (int) $row->country_id : null;
    }

    /**
     * Naechstgelegene Zeile einer Tabelle per Haversine, mit Bounding-Box-Vorfilter.
     */
    private function nearestByHaversine(
        string $table,
        string $latColumn,
        string $lngColumn,
        string $select,
        float $latitude,
        float $longitude,
        float $maxDistanceKm,
        string $extraWhere,
    ): ?object {
        // 1 Breitengrad entspricht rund 111 km; beim Laengengrad kommt cos(lat) dazu.
        $latDelta = $maxDistanceKm / 111.0;
        $lngDelta = $maxDistanceKm / max(1.0, 111.0 * cos(deg2rad($latitude)));

        $sql = "select {$select}, "
            . '(6371 * acos(least(1, greatest(-1, '
            . "cos(radians(?)) * cos(radians({$latColumn})) * cos(radians({$lngColumn}) - radians(?)) "
            . "+ sin(radians(?)) * sin(radians({$latColumn}))"
            . ')))) as distance_km '
            . "from {$table} "
            . "where {$extraWhere} "
            . "and {$latColumn} is not null and {$lngColumn} is not null "
            . "and {$latColumn} between ? and ? "
            . "and {$lngColumn} between ? and ? "
            . 'having distance_km <= ? '
            . 'order by distance_km '
            . 'limit 1';

        $rows = DB::select($sql, [
            $latitude, $longitude, $latitude,
            $latitude - $latDelta, $latitude + $latDelta,
            $longitude - $lngDelta, $longitude + $lngDelta,
            $maxDistanceKm,
        ]);

        return $rows[0] ?? null;
    }
}

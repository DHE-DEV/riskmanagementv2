<?php

namespace App\Http\Controllers;

use App\Models\Airport;
use App\Models\Country;
use App\Models\Continent;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Schema;

class AirportSearchController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));
        $countryFilter = trim((string) $request->query('country', ''));
        $countryId = (int) $request->query('country_id', 0);
        $continentFilter = trim((string) $request->query('continent', ''));

        if ($query === '' && $countryFilter === '' && $continentFilter === '' && $countryId === 0) {
            return response()->json([
                'data' => [],
            ]);
        }

        $airportTable = (new Airport())->getTable();
        $selectColumns = [
            $airportTable.'.id as id',
            $airportTable.'.name as name',
            $airportTable.'.iata_code as iata_code',
            $airportTable.'.icao_code as icao_code',
        ];
        // Unterstütze beide Varianten: latitude/longitude ODER lat/lng (je nach DB-Schema)
        if (Schema::hasColumn('airports', 'latitude') && Schema::hasColumn('airports', 'longitude')) {
            $selectColumns[] = $airportTable.'.latitude as latitude';
            $selectColumns[] = $airportTable.'.longitude as longitude';
        } elseif (Schema::hasColumn('airports', 'lat') && Schema::hasColumn('airports', 'lng')) {
            $selectColumns[] = $airportTable.'.lat as latitude';
            $selectColumns[] = $airportTable.'.lng as longitude';
        }

        $airportsQuery = Airport::query()->withoutGlobalScopes()
            ->select($selectColumns)
            ->when($countryFilter !== '' || $continentFilter !== '' || $countryId !== 0, function ($q) use ($countryFilter, $continentFilter, $countryId, $airportTable) {
                $q->leftJoin('countries', $airportTable.'.country_id', '=', 'countries.id');
                if ($continentFilter !== '') {
                    $q->leftJoin('continents', 'countries.continent_id', '=', 'continents.id');
                }
                if ($countryId !== 0) {
                    $q->where('countries.id', '=', $countryId);
                }
            })
            ->when(strlen($query) === 3, function ($q) use ($query) {
                $q->where('iata_code', 'like', strtoupper($query));
            }, function ($q) use ($query) {
                $q->where(function ($sub) use ($query) {
                    $sub->where('name', 'like', "%{$query}%")
                        ->orWhere('iata_code', 'like', "%{$query}%")
                        ->orWhere('icao_code', 'like', "%{$query}%");
                });
            })
            ->when($countryFilter !== '' && $countryId === 0, function ($q) use ($countryFilter) {
                $q->where(function ($inner) use ($countryFilter) {
                    $upper = strtoupper($countryFilter);
                    // name (legacy schema)
                    if (Schema::hasColumn('countries', 'name')) {
                        $inner->orWhere('countries.name', 'like', "%{$countryFilter}%");
                    }
                    // name_translations->de / ->en (new schema)
                    if (Schema::hasColumn('countries', 'name_translations')) {
                        $inner->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(countries.name_translations, '$.de')) LIKE ?", ["%{$countryFilter}%"]) 
                              ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(countries.name_translations, '$.en')) LIKE ?", ["%{$countryFilter}%"]);
                    }
                    // codes
                    if (Schema::hasColumn('countries', 'code')) {
                        $inner->orWhere('countries.code', 'like', "%{$upper}%");
                    }
                    if (Schema::hasColumn('countries', 'iso3')) {
                        $inner->orWhere('countries.iso3', 'like', "%{$upper}%");
                    }
                    if (Schema::hasColumn('countries', 'iso_code')) {
                        $inner->orWhere('countries.iso_code', 'like', "%{$upper}%");
                    }
                    if (Schema::hasColumn('countries', 'iso3_code')) {
                        $inner->orWhere('countries.iso3_code', 'like', "%{$upper}%");
                    }
                });
            })
            ->when($continentFilter !== '', function ($q) use ($continentFilter) {
                $q->where(function ($inner) use ($continentFilter) {
                    $upper = strtoupper($continentFilter);
                    if (Schema::hasColumn('continents', 'name')) {
                        $inner->orWhere('continents.name', 'like', "%{$continentFilter}%");
                    }
                    if (Schema::hasColumn('continents', 'code')) {
                        $inner->orWhere('continents.code', 'like', "%{$upper}%");
                    }
                });
            })
            ->orderBy('name');

        // Limit dynamisch: bei leerem Suchbegriff aber gesetztem Country/Continent mehr Ergebnisse zulassen
        $limit = 20;
        if ($query === '' && ($countryFilter !== '' || $continentFilter !== '' || $countryId !== 0)) {
            $limit = 200;
        }
        $airportsQuery->limit($limit);

        $results = $airportsQuery->get()->map(function (Airport $airport) {
            return [
                'id' => $airport->id,
                'name' => $airport->name,
                'iata_code' => $airport->iata_code,
                'icao_code' => $airport->icao_code,
                'latitude' => $airport->latitude ?? null,
                'longitude' => $airport->longitude ?? null,
            ];
        });

        return response()->json([
            'data' => $results,
        ]);
    }

    public function countries(): JsonResponse
    {
        $countries = Country::query()
            ->select(['countries.id', 'countries.name', 'countries.code'])
            ->join('airports', 'airports.country_id', '=', 'countries.id')
            ->distinct()
            ->orderBy('countries.name')
            ->get();

        return response()->json([
            'data' => $countries,
        ]);
    }

    public function continents(): JsonResponse
    {
        $continents = Continent::query()
            ->select(['continents.id', 'continents.name'])
            ->join('countries', 'countries.continent_id', '=', 'continents.id')
            ->join('airports', 'airports.country_id', '=', 'countries.id')
            ->distinct()
            ->orderBy('continents.name')
            ->get();

        return response()->json([
            'data' => $continents,
        ]);
    }

    public function countrySearch(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        if ($q === '') {
            return response()->json(['data' => []]);
        }

        $countries = Country::query()
            ->withoutGlobalScopes()
            ->select(['id', 'iso_code', 'iso3_code', 'name_translations'])
            ->where(function ($qb) use ($q) {
                $qb->where('iso_code', 'like', "%{$q}%")
                   ->orWhere('iso3_code', 'like', "%{$q}%")
                   ->orWhereRaw("JSON_EXTRACT(name_translations, '$.de') LIKE ?", ["%{$q}%"]) 
                   ->orWhereRaw("JSON_EXTRACT(name_translations, '$.en') LIKE ?", ["%{$q}%"]);
            })
            ->orderBy('iso_code')
            ->limit(20)
            ->get()
            ->map(function (Country $c) {
                return [
                    'id' => $c->id,
                    'iso2' => $c->iso_code,
                    'iso3' => $c->iso3_code,
                    'name' => $c->getName('de'),
                ];
            });

        return response()->json(['data' => $countries]);
    }

    public function countryLocate(Request $request): JsonResponse
    {
        $countryId = (int) $request->query('country_id', 0);
        $q = trim((string) $request->query('q', ''));

        $query = Country::query()->withoutGlobalScopes();
        if ($countryId > 0) {
            $query->where('id', $countryId);
        } elseif ($q !== '') {
            $upper = strtoupper($q);
            $query->where(function ($qb) use ($q, $upper) {
                if (Schema::hasColumn('countries', 'name')) {
                    $qb->orWhere('name', 'like', "%{$q}%");
                }
                if (Schema::hasColumn('countries', 'name_translations')) {
                    $qb->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(name_translations, '$.de')) LIKE ?", ["%{$q}%"]) 
                       ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(name_translations, '$.en')) LIKE ?", ["%{$q}%"]);
                }
                if (Schema::hasColumn('countries', 'iso_code')) {
                    $qb->orWhere('iso_code', 'like', "%{$upper}%");
                }
                if (Schema::hasColumn('countries', 'iso3_code')) {
                    $qb->orWhere('iso3_code', 'like', "%{$upper}%");
                }
                if (Schema::hasColumn('countries', 'code')) {
                    $qb->orWhere('code', 'like', "%{$upper}%");
                }
                if (Schema::hasColumn('countries', 'iso3')) {
                    $qb->orWhere('iso3', 'like', "%{$upper}%");
                }
            });
        } else {
            return response()->json(['data' => null]);
        }

        $country = $query->select(['id', 'lat', 'lng'])->first();
        if (!$country) {
            return response()->json(['data' => null]);
        }

        return response()->json(['data' => [
            'id' => $country->id,
            'latitude' => $country->lat,
            'longitude' => $country->lng,
        ]]);
    }
}



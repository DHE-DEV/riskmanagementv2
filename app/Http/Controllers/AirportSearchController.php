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
            ->select(['countries.id', 'countries.iso_code', 'countries.name_translations'])
            ->join('airports', 'airports.country_id', '=', 'countries.id')
            ->distinct()
            ->orderBy('countries.iso_code')
            ->get()
            ->map(function ($country) {
                return [
                    'id' => $country->id,
                    'code' => $country->iso_code,
                    'name' => $country->getName('de'),
                ];
            });

        return response()->json([
            'data' => $countries,
        ]);
    }

    public function continents(): JsonResponse
    {
        $continents = Continent::query()
            ->select(['continents.id', 'continents.code', 'continents.name_translations'])
            ->join('countries', 'countries.continent_id', '=', 'continents.id')
            ->join('airports', 'airports.country_id', '=', 'countries.id')
            ->distinct()
            ->orderBy('continents.code')
            ->get()
            ->map(function ($continent) {
                return [
                    'id' => $continent->id,
                    'name' => $continent->getName('de'),
                ];
            });

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

        try {
            // Simple approach: get all countries and filter in PHP
            $allCountries = Country::query()
                ->withoutGlobalScopes()
                ->select(['id', 'iso_code', 'iso3_code', 'name_translations'])
                ->orderBy('iso_code')
                ->get();

            // Normalize search query for better matching
            $normalizedQ = $this->normalizeString($q);

            $filteredCountries = $allCountries->filter(function (Country $country) use ($q, $normalizedQ) {
                $name = $country->getName('de');
                $iso2 = $country->iso_code;
                $iso3 = $country->iso3_code;

                // Normalize country name for comparison
                $normalizedName = $this->normalizeString($name);

                // Check both original and normalized strings
                return stripos($name, $q) !== false ||
                       stripos($iso2, $q) !== false ||
                       stripos($iso3, $q) !== false ||
                       stripos($normalizedName, $normalizedQ) !== false;
            })->take(20)->map(function (Country $c) {
                return [
                    'id' => $c->id,
                    'iso2' => $c->iso_code,
                    'iso3' => $c->iso3_code,
                    'name' => $c->getName('de'),
                ];
            });

            return response()->json(['data' => $filteredCountries->values()]);
        } catch (\Exception $e) {
            \Log::error('Country search error: ' . $e->getMessage());
            return response()->json(['data' => [], 'error' => $e->getMessage()]);
        }
    }

    /**
     * Normalize string by removing diacritics and special characters for better search matching
     */
    private function normalizeString(string $str): string
    {
        // Convert to lowercase
        $str = mb_strtolower($str, 'UTF-8');

        // Replace common German umlauts and special characters
        $replacements = [
            'ä' => 'a', 'ö' => 'o', 'ü' => 'u', 'ß' => 'ss',
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'å' => 'a',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
            'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
            'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o',
            'ù' => 'u', 'ú' => 'u', 'û' => 'u',
            'ñ' => 'n', 'ç' => 'c', 'ø' => 'o', 'æ' => 'ae',
        ];

        return strtr($str, $replacements);
    }

    public function countrySearchDebug(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        if ($q === '') {
            return response()->json(['data' => []]);
        }

        // Debug: Alle Länder anzeigen, die "deut" enthalten
        $debugCountries = Country::query()
            ->withoutGlobalScopes()
            ->select(['id', 'iso_code', 'iso3_code', 'name_translations', 'name'])
            ->get()
            ->filter(function ($country) use ($q) {
                $name = $country->getName('de');
                $iso2 = $country->iso_code;
                $iso3 = $country->iso3_code;
                $legacyName = $country->name ?? '';
                
                return stripos($name, $q) !== false || 
                       stripos($iso2, $q) !== false || 
                       stripos($iso3, $q) !== false ||
                       stripos($legacyName, $q) !== false;
            })
            ->map(function (Country $c) {
                return [
                    'id' => $c->id,
                    'iso2' => $c->iso_code,
                    'iso3' => $c->iso3_code,
                    'name_de' => $c->getName('de'),
                    'name_en' => $c->getName('en'),
                    'legacy_name' => $c->name ?? 'null',
                    'name_translations_raw' => $c->name_translations,
                ];
            });

        return response()->json([
            'query' => $q,
            'debug_countries' => $debugCountries,
            'total_found' => $debugCountries->count()
        ]);
    }

    public function countryLocate(Request $request): JsonResponse
    {
        $countryId = (int) $request->query('country_id', 0);
        $q = trim((string) $request->query('q', ''));

        // First try exact match on iso_code or iso3_code
        $upper = strtoupper($q);
        $country = null;

        if ($countryId > 0) {
            $country = Country::withoutGlobalScopes()
                ->where('id', $countryId)
                ->first();
        } elseif ($q !== '') {
            // Try exact match first
            if (Schema::hasColumn('countries', 'iso_code')) {
                $country = Country::withoutGlobalScopes()
                    ->where('iso_code', $upper)
                    ->first();
            }

            // If no exact match, try iso3_code
            if (!$country && Schema::hasColumn('countries', 'iso3_code')) {
                $country = Country::withoutGlobalScopes()
                    ->where('iso3_code', $upper)
                    ->first();
            }

            // If still no match, try fuzzy search
            if (!$country) {
                $query = Country::query()->withoutGlobalScopes();
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
                });
                $country = $query->first();
            }
        } else {
            return response()->json(['data' => null]);
        }
        if (!$country) {
            return response()->json(['data' => null]);
        }

        return response()->json(['data' => [
            'id' => $country->id,
            'name' => $country->getName('de') ?? $country->name,
            'iso_code' => $country->iso_code,
            'latitude' => $country->lat,
            'longitude' => $country->lng,
        ]]);
    }

    /**
     * Get all country name mappings for the frontend
     */
    public function getCountryMappings()
    {
        try {
            $countries = Country::all();
            $mappings = [];

            foreach ($countries as $country) {
                $translations = $country->name_translations ?? [];

                // Get German and English names
                $nameDE = $translations['de'] ?? null;
                $nameEN = $translations['en'] ?? $country->name ?? null;

                // Create mapping entries for all available translations
                foreach ($translations as $lang => $name) {
                    if ($name) {
                        $normalizedName = strtolower(trim($name));

                        // Map to English name as primary
                        if (!isset($mappings[$normalizedName])) {
                            $mappings[$normalizedName] = [];
                        }

                        // Add all possible variations
                        if ($nameEN && !in_array(strtolower($nameEN), $mappings[$normalizedName])) {
                            $mappings[$normalizedName][] = strtolower($nameEN);
                        }

                        // Also add ISO codes
                        if ($country->iso_code) {
                            $mappings[$normalizedName][] = strtolower($country->iso_code);
                        }
                        if ($country->iso3_code) {
                            $mappings[$normalizedName][] = strtolower($country->iso3_code);
                        }
                    }
                }

                // Also map ISO codes to country names
                if ($country->iso_code) {
                    $mappings[strtolower($country->iso_code)] = [strtolower($nameEN ?? '')];
                }
                if ($country->iso3_code) {
                    $mappings[strtolower($country->iso3_code)] = [strtolower($nameEN ?? '')];
                }

                // Special case for Cape Verde/Cabo Verde
                if ($country->iso_code === 'CV') {
                    // Add "Cabo Verde" as a variation for "Kap Verde"
                    if (isset($mappings['kap verde'])) {
                        $mappings['kap verde'][] = 'cabo verde';
                    }
                    // Also add direct mapping for "Cabo Verde"
                    $mappings['cabo verde'] = ['cape verde', 'kap verde', 'cv', 'cpv'];
                }

                // Special case for Czech Republic/Czechia
                if ($country->iso_code === 'CZ') {
                    if (isset($mappings['tschechische republik'])) {
                        $mappings['tschechische republik'][] = 'czechia';
                    }
                    $mappings['czechia'] = ['czech republic', 'tschechische republik', 'cz', 'cze'];
                    $mappings['tschechien'] = ['czechia', 'czech republic', 'cz', 'cze'];
                }
            }

            return response()->json([
                'success' => true,
                'data' => $mappings,
                'count' => count($mappings)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load country mappings: ' . $e->getMessage()
            ], 500);
        }
    }
}



<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AirportResource;
use App\Http\Resources\Api\V1\CountryDetailResource;
use App\Models\Airport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Flughaefen fuer die Kunden-API.
 *
 *   GET /v1/airports?country=EG&q=cairo   – Liste (Kurzform)
 *   GET /v1/airports/{code}                – ein Flughafen per IATA (3) oder ICAO (4) mit allem
 */
class AirportController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'country' => 'nullable|string|max:3',
            'q' => 'nullable|string|max:100',
            'type' => 'nullable|string|max:30',
            'lang' => 'nullable|string|max:5',
        ]);

        $lang = CountryDetailResource::lang($request);
        $query = Airport::query()
            ->with(['city', 'country'])
            ->withCount(['airlines' => fn ($q) => $q->where('is_active', true)])
            ->where('is_active', true)
            ->orderBy('name');

        if ($country = strtoupper(trim((string) $request->query('country')))) {
            $query->whereHas('country', fn ($q) => $q->where(strlen($country) === 2 ? 'iso_code' : 'iso3_code', $country));
        }

        if ($type = trim((string) $request->query('type'))) {
            $query->where('type', $type);
        }

        if ($term = trim((string) $request->query('q'))) {
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', '%'.$term.'%')
                    ->orWhere('iata_code', strtoupper($term))
                    ->orWhere('icao_code', strtoupper($term));
            });
        }

        $airports = $query->get();

        return response()->json([
            'success' => true,
            'data' => $airports->map(fn (Airport $airport) => AirportResource::summary($airport, $lang))->values()->all(),
            'meta' => ['total' => $airports->count()],
        ]);
    }

    public function show(Request $request, string $code): JsonResponse
    {
        $request->validate(['lang' => 'nullable|string|max:5']);

        $airport = self::find($code);

        if (! $airport) {
            return response()->json(['success' => false, 'message' => 'Airport not found.'], 404);
        }

        $airport->load([
            'city', 'country',
            'airlines' => fn ($q) => $q->where('is_active', true)->orderBy('name'),
        ]);

        return response()->json([
            'success' => true,
            'data' => AirportResource::detail($airport, CountryDetailResource::lang($request)),
        ]);
    }

    /**
     * Flughafen per IATA- (3 Zeichen) oder ICAO-Code (4 Zeichen), Gross-/Kleinschreibung egal.
     */
    public static function find(string $code): ?Airport
    {
        $code = strtoupper(trim($code));

        if (! preg_match('/^[A-Z0-9]{3,4}$/', $code)) {
            return null;
        }

        return Airport::query()
            ->where('is_active', true)
            ->where(strlen($code) === 3 ? 'iata_code' : 'icao_code', $code)
            ->first();
    }
}

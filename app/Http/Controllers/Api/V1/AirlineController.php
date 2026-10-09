<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AirlineResource;
use App\Http\Resources\Api\V1\CountryDetailResource;
use App\Models\Airline;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Fluggesellschaften fuer die Kunden-API.
 *
 *   GET /v1/airlines?country=EG&q=egypt            – Liste (Kurzform)
 *   GET /v1/airlines/{code}?include=lounges,hotels  – eine Airline per IATA (2) oder ICAO (3) mit allem
 */
class AirlineController extends Controller
{
    public const INCLUDES = ['lounges', 'hotels'];

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'country' => 'nullable|string|max:3',
            'q' => 'nullable|string|max:100',
            'lang' => 'nullable|string|max:5',
        ]);

        $lang = CountryDetailResource::lang($request);
        $query = Airline::query()
            ->with('homeCountry')
            ->withCount(['airports' => fn ($q) => $q->where('is_active', true)])
            ->where('is_active', true)
            ->orderBy('name');

        if ($country = strtoupper(trim((string) $request->query('country')))) {
            $query->whereHas('homeCountry', fn ($q) => $q->where(strlen($country) === 2 ? 'iso_code' : 'iso3_code', $country));
        }

        if ($term = trim((string) $request->query('q'))) {
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', '%'.$term.'%')
                    ->orWhere('iata_code', strtoupper($term))
                    ->orWhere('icao_code', strtoupper($term));
            });
        }

        $airlines = $query->get();

        return response()->json([
            'success' => true,
            'data' => $airlines->map(fn (Airline $airline) => AirlineResource::summary($airline, $lang))->values()->all(),
            'meta' => ['total' => $airlines->count()],
        ]);
    }

    public function show(Request $request, string $code): JsonResponse
    {
        $request->validate([
            'lang' => 'nullable|string|max:5',
            'include' => 'nullable|string|max:50',
        ]);

        $airline = self::find($code);

        if (! $airline) {
            return response()->json(['success' => false, 'message' => 'Airline not found.'], 404);
        }

        $include = array_values(array_intersect(self::INCLUDES, array_map('trim', explode(',', (string) $request->query('include')))));

        $airline->load([
            'homeCountry',
            'airports' => fn ($q) => $q->where('is_active', true)->with(['city', 'country'])->orderBy('name'),
        ]);

        return response()->json([
            'success' => true,
            'data' => AirlineResource::detail($airline, CountryDetailResource::lang($request), $include),
        ]);
    }

    /**
     * Airline per IATA- (2 Zeichen) oder ICAO-Code (3 Zeichen), Gross-/Kleinschreibung egal.
     */
    public static function find(string $code): ?Airline
    {
        $code = strtoupper(trim($code));

        if (! preg_match('/^[A-Z0-9]{2,3}$/', $code)) {
            return null;
        }

        return Airline::query()
            ->where('is_active', true)
            ->where(strlen($code) === 2 ? 'iata_code' : 'icao_code', $code)
            ->first();
    }
}

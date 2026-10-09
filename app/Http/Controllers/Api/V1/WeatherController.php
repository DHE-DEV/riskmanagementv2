<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\WeatherResource;
use App\Services\WeatherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Wetter fuer die Kunden-API – dieselbe Quelle wie auf der Plattform (OpenWeatherMap, Server-Schluessel).
 *
 *   GET /v1/weather?lat=52.52&lng=13.405&lang=de   – aktuelles Wetter, 24 Stunden und 5 Tage fuer einen Punkt
 *   GET /v1/countries/{code}/weather?lang=de         – dasselbe fuer die Hauptstadt (sonst Landesmitte)
 */
class WeatherController extends Controller
{
    public function __construct(private readonly WeatherService $weather) {}

    public function point(Request $request): JsonResponse
    {
        $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'lang' => 'nullable|string|max:5',
        ]);

        return $this->report((float) $request->input('lat'), (float) $request->input('lng'), self::lang($request), null);
    }

    public function country(Request $request, string $code): JsonResponse
    {
        $request->validate(['lang' => 'nullable|string|max:5']);

        $country = BaseDataController::findCountry($code);
        if (! $country) {
            return response()->json(['success' => false, 'message' => 'Country not found.'], 404);
        }

        $country->loadMissing('capital');
        $lang = self::lang($request);
        $capital = $country->capital;

        if ($capital && $capital->lat !== null && $capital->lng !== null) {
            return $this->report((float) $capital->lat, (float) $capital->lng, $lang, $capital->getName($lang));
        }

        if ($country->lat !== null && $country->lng !== null) {
            return $this->report((float) $country->lat, (float) $country->lng, $lang, $country->getName($lang));
        }

        return response()->json(['success' => false, 'message' => 'No coordinates for this country.'], 404);
    }

    private function report(float $lat, float $lng, string $lang, ?string $name): JsonResponse
    {
        $current = $this->weather->getCurrentWeather($lat, $lng, $lang);
        $forecast = $this->weather->getForecast($lat, $lng, $lang);

        if ($current === null) {
            return response()->json(['success' => false, 'message' => 'Weather service unavailable.'], 503);
        }

        return response()->json([
            'success' => true,
            'data' => WeatherResource::report($current, $forecast, $lat, $lng, $name),
        ]);
    }

    /** OpenWeatherMap kennt de, en, nl – alles andere faellt auf Deutsch zurueck. */
    private static function lang(Request $request): string
    {
        $lang = strtolower(trim((string) $request->query('lang', 'de')));

        return in_array($lang, ['de', 'en', 'nl'], true) ? $lang : 'de';
    }
}

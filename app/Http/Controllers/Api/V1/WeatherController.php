<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\WeatherResource;
use App\Services\WeatherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Wetter fuer die Kunden-API – dieselbe Quelle wie auf der Plattform (OpenWeatherMap, Server-Schluessel).
 *
 *   GET /v1/weather?lat=52.52&lng=13.405&lang=de   – aktuelles Wetter, 24 Stunden und 5 Tage fuer einen Punkt
 *   GET /v1/countries/{code}/weather?lang=de         – dasselbe fuer die Hauptstadt (sonst Landesmitte)
 *   GET /v1/weather/daily?lat=52.52&lng=13.405       – Tagesvorhersage fuer 16 Tage (Open-Meteo, ohne Schluessel),
 *                                                      fuer den Tagesplan einer Reise; 3 Stunden zwischengespeichert
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

    /** WMO-Wettercode (Open-Meteo) → Art des Wetters fuer die Symbole der Apps. */
    private const CONDITIONS = [
        0 => 'clear', 1 => 'partly', 2 => 'partly', 3 => 'cloudy', 45 => 'fog', 48 => 'fog',
        51 => 'drizzle', 53 => 'drizzle', 55 => 'drizzle', 56 => 'drizzle', 57 => 'drizzle',
        61 => 'rain', 63 => 'rain', 65 => 'rain', 66 => 'rain', 67 => 'rain', 80 => 'showers', 81 => 'showers', 82 => 'showers',
        71 => 'snow', 73 => 'snow', 75 => 'snow', 77 => 'snow', 85 => 'snow', 86 => 'snow', 95 => 'thunder', 96 => 'thunder', 99 => 'thunder',
    ];

    public function daily(Request $request): JsonResponse
    {
        $data = $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
        ]);
        $lat = round((float) $data['lat'], 2);
        $lng = round((float) $data['lng'], 2);

        $days = Cache::remember("weather-daily:{$lat}:{$lng}", now()->addHours(3), function () use ($lat, $lng) {
            $response = Http::timeout(10)->get('https://api.open-meteo.com/v1/forecast', [
                'latitude' => $lat,
                'longitude' => $lng,
                'daily' => 'weather_code,temperature_2m_max,temperature_2m_min,precipitation_probability_max',
                'forecast_days' => 16,
                'timezone' => 'auto',
            ]);
            if (! $response->successful()) {
                return null;
            }
            $daily = (array) $response->json('daily', []);

            return collect($daily['time'] ?? [])->map(fn ($day, $i) => [
                'date' => $day,
                'condition' => self::CONDITIONS[(int) ($daily['weather_code'][$i] ?? -1)] ?? 'cloudy',
                'temp_max' => isset($daily['temperature_2m_max'][$i]) ? round((float) $daily['temperature_2m_max'][$i]) : null,
                'temp_min' => isset($daily['temperature_2m_min'][$i]) ? round((float) $daily['temperature_2m_min'][$i]) : null,
                'precipitation_probability' => $daily['precipitation_probability_max'][$i] ?? null,
            ])->values()->all();
        });

        if ($days === null) {
            Cache::forget("weather-daily:{$lat}:{$lng}");

            return response()->json(['success' => false, 'message' => 'Weather service unavailable.'], 503);
        }

        return response()->json(['success' => true, 'data' => ['lat' => $lat, 'lng' => $lng, 'days' => $days]]);
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

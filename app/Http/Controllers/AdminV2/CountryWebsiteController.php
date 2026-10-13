<?php

namespace App\Http\Controllers\AdminV2;

use App\Http\Controllers\Api\V1\BaseDataController;
use App\Http\Controllers\Api\V1\CountryPlacesController;
use App\Http\Controllers\Api\V1\WeatherController;
use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Services\ExchangeRateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

/**
 * Stammdaten > Länder > „Website-Ansicht“: alle Länderdaten als gestaltete Seite – mit denselben
 * Reitern wie die TripData-App (Allgemeines, Regionen, Städte, Sehenswert, Transport, Geld, Notfall,
 * Wetter, eSIM, Visa, Gesundheit).
 *
 * Die Daten kommen bewusst aus denselben API-Controllern wie GET /v1/countries/{code} und
 * /v1/countries/{code}/regions|subregions|cities|sights|weather: Was hier steht, sieht die App genauso.
 */
class CountryWebsiteController extends Controller
{
    public const LANGUAGES = ['de' => 'Deutsch', 'en' => 'English', 'nl' => 'Nederlands'];

    public function __invoke(Request $request, Country $country): View
    {
        $lang = array_key_exists($request->query('lang'), self::LANGUAGES) ? $request->query('lang') : 'de';
        $apiRequest = Request::create('/', 'GET', ['lang' => $lang]);
        $code = (string) $country->iso_code;

        $detail = $this->data(fn () => app(BaseDataController::class)->country($apiRequest, $code));
        abort_if($detail === null, 404, 'Für dieses Land liefert die API keine Daten (ISO-Code fehlt?).');

        $places = app(CountryPlacesController::class);

        return view('adminv2.country-website', [
            'country' => $country,
            'lang' => $lang,
            'languages' => self::LANGUAGES,
            'c' => $detail,
            'regions' => $this->data(fn () => $places->regions($apiRequest, $code)) ?? [],
            'subregions' => $this->data(fn () => $places->subregions($apiRequest, $code)) ?? [],
            'cities' => $this->data(fn () => $places->cities($apiRequest, $code)) ?? [],
            'sights' => $this->data(fn () => $places->sights($apiRequest, $code)) ?? [],
            // Wetter und Kurse kommen von aussen: fehlen sie, bleibt der Bereich mit Hinweis leer.
            'weather' => $this->data(fn () => app(WeatherController::class)->country($apiRequest, $code)),
            'rates' => $this->rates(),
        ]);
    }

    /** `data` einer API-Antwort; null bei Fehlerstatus oder Ausnahme. */
    private function data(callable $call): ?array
    {
        try {
            /** @var JsonResponse $response */
            $response = $call();
            if ($response->getStatusCode() >= 400) {
                return null;
            }
            $payload = $response->getData(true);

            return is_array($payload['data'] ?? null) ? $payload['data'] : null;
        } catch (Throwable $e) {
            Log::warning('Länder-Website: Daten nicht ladbar', ['error' => $e->getMessage()]);

            return null;
        }
    }

    private function rates(): ?array
    {
        try {
            return app(ExchangeRateService::class)->latest();
        } catch (Throwable) {
            return null;
        }
    }
}

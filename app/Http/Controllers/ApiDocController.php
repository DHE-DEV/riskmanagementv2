<?php

namespace App\Http\Controllers;

use App\Services\ApiDocRenderer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * API-Dokumentation fuer Kunden und Partner.
 *
 *   api.global-travel-monitor.de/              Startseite mit allen Anleitungen
 *   api.global-travel-monitor.de/v1            Endpoint-Uebersicht als JSON
 *   api.global-travel-monitor.de/docs/{file}   Anleitung oder OpenAPI-Datei herunterladen
 *
 * Dasselbe auf der Plattform unter /api/v1/documentation, /api/v1 und /api/v1/documentation/{file}
 * sowie als einzelne Seiten unter /docs/api/{guide}.
 */
class ApiDocController extends Controller
{
    public function __construct(private readonly ApiDocRenderer $docs) {}

    /**
     * Startseite der API-Dokumentation mit eingebetteten Anleitungen.
     */
    public function landing(Request $request): View
    {
        $onApiDomain = $request->getHost() === config('app.api_domain');

        return view('api.landing', [
            'apiBase' => $request->getSchemeAndHttpHost().($onApiDomain ? '' : '/api'),
            'downloadBase' => $onApiDomain ? '/docs' : '/api/v1/documentation',
            'sections' => $this->docs->landingSections(),
        ]);
    }

    /**
     * Endpoint-Uebersicht als JSON (ohne Token erreichbar).
     */
    public function overview(Request $request): JsonResponse
    {
        $onApiDomain = $request->getHost() === config('app.api_domain');

        return response()->json([
            'name' => 'Global Travel Monitor API',
            'version' => 'v1',
            'documentation' => $onApiDomain ? $request->getSchemeAndHttpHost().'/' : $request->getSchemeAndHttpHost().'/api/v1/documentation',
            'endpoints' => [
                'Events (alle)' => '/v1/events',
                'Länder mit aktiven Events' => '/v1/events/countries',
                'Events im Umkreis' => '/v1/events/nearby?code=FRA&radius=300',
                'Custom Events (Partner)' => '/v1/custom/events',
                'Folder Import API' => '/v1/folders',
                'Basisdaten' => [
                    'Kontinente' => '/v1/continents',
                    'Länder' => '/v1/countries',
                    'Länderinformationen' => '/v1/countries/{code}',
                    'Landesgrenze (GeoJSON)' => '/v1/countries/{code}/boundary',
                    'Regionen eines Landes' => '/v1/countries/{code}/regions',
                    'Städte eines Landes' => '/v1/countries/{code}/cities?region={id}',
                    'Sehenswürdigkeiten eines Landes' => '/v1/countries/{code}/sights?region={id}&highlight=1',
                    'Einzelne Sehenswürdigkeit' => '/v1/sights/{id}',
                    'Gemerkte Orte auflösen' => '/v1/places?keys=region:12,city:5,sight:3',
                    'Wetter der Hauptstadt' => '/v1/countries/{code}/weather',
                    'Wetter für Koordinaten' => '/v1/weather?lat=52.52&lng=13.405',
                    'Landesgrenzen mehrerer Länder' => '/v1/boundaries?codes=EG,DE',
                    'Flughäfen' => '/v1/airports?country=EG',
                    'Flughafen mit Lounges, Hotels, Mobilität, Airlines' => '/v1/airports/{code}',
                    'Airlines' => '/v1/airlines?country=EG',
                    'Airline mit Kontakt, Gepäck, Tieren, Flughäfen' => '/v1/airlines/{code}',
                    'Wechselkurse' => '/v1/exchange-rates',
                    'Regionen' => '/v1/regions',
                    'Event-Kategorien' => '/v1/event-categories',
                ],
                'Referenzdaten (Partner)' => ['/v1/custom/event-categories', '/v1/custom/countries'],
                'Plugin GTM Domain Management' => '/v1/plugin/gtm/domains',
            ],
            'base_url' => $request->getSchemeAndHttpHost().($onApiDomain ? '' : '/api'),
            'authentication' => 'Bearer Token via Authorization header',
        ]);
    }

    /**
     * Anleitung (Markdown) oder OpenAPI-Datei (YAML) herunterladen.
     */
    public function download(string $file): BinaryFileResponse
    {
        $path = $this->docs->downloadPath($file);

        if ($path === null) {
            abort(404);
        }

        return response()->file($path, [
            'Content-Type' => str_ends_with($file, '.yaml') ? 'application/x-yaml' : 'text/markdown',
            'Content-Disposition' => "attachment; filename=\"{$file}\"",
        ]);
    }

    /**
     * Einzelne Anleitung als Doku-Seite mit Seitenleiste (/docs/api/{guide}).
     */
    public function show(string $guide): View
    {
        $meta = $this->docs->guide($guide);

        if ($meta === null) {
            abort(404);
        }

        $rendered = $this->docs->render($guide, richCode: true);

        return view('docs.api.guide', [
            'guide' => $meta,
            'html' => $rendered['html'],
            'toc' => $rendered['toc'],
        ]);
    }
}

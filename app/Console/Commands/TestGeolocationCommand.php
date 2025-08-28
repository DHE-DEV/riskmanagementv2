<?php

namespace App\Console\Commands;

use App\Services\GeolocationService;
use App\Services\ReverseGeocodingService;
use Illuminate\Console\Command;

class TestGeolocationCommand extends Command
{
    protected $signature = 'geolocation:test {lat} {lng} {--method=database}';

    protected $description = 'Teste die Geolocation-Services mit gegebenen Koordinaten';

    public function handle(GeolocationService $geolocationService, ReverseGeocodingService $reverseGeocodingService): int
    {
        $lat = (float) $this->argument('lat');
        $lng = (float) $this->argument('lng');
        $method = $this->option('method');

        $this->info("Teste Geolocation für Koordinaten: {$lat}, {$lng}");
        $this->info("Verwende Methode: {$method}");
        $this->newLine();

        try {
            // Datenbank-basierte Lösung
            $this->info('=== Datenbank-basierte Lösung ===');
            $databaseResult = $geolocationService->findLocationInfo($lat, $lng);
            $this->displayResult($databaseResult);

            // API-basierte Lösung (falls gewünscht)
            if ($method === 'nominatim') {
                $this->newLine();
                $this->info('=== Nominatim API-basierte Lösung ===');
                $nominatimResult = $reverseGeocodingService->getLocationFromCoordinates($lat, $lng);
                $this->displayResult($nominatimResult);
            }

            // Zusätzliche Informationen
            $this->newLine();
            $this->info('=== Zusätzliche Informationen ===');

            $nearestCity = $geolocationService->findNearestCity($lat, $lng, 100);
            if ($nearestCity) {
                $this->line("Nächstgelegene Stadt: {$nearestCity->getName()} (ID: {$nearestCity->id})");
            }

            $citiesInRadius = $geolocationService->findCitiesInRadius($lat, $lng, 50);
            $this->line("Städte im 50km Radius: {$citiesInRadius->count()}");

        } catch (\Exception $e) {
            $this->error("Fehler: {$e->getMessage()}");

            return 1;
        }

        return 0;
    }

    private function displayResult(array $result): void
    {
        $this->line("Koordinaten: {$result['coordinates']['lat']}, {$result['coordinates']['lng']}");

        if ($result['city']) {
            $this->line("Stadt: {$result['city']['name']} (ID: {$result['city']['id']})");
            $this->line("Entfernung: {$result['city']['distance_km']} km");
            $this->line('Hauptstadt: '.($result['city']['is_capital'] ? 'Ja' : 'Nein'));
        }

        if ($result['country']) {
            $this->line("Land: {$result['country']['name']} (ID: {$result['country']['id']})");
            $this->line("ISO Code: {$result['country']['iso_code']}");
            $this->line("Entfernung: {$result['country']['distance_km']} km");
        }

        if ($result['continent']) {
            $this->line("Kontinent: {$result['continent']['name']} (ID: {$result['continent']['id']})");
            $this->line("Code: {$result['continent']['code']}");
            $this->line("Entfernung: {$result['continent']['distance_km']} km");
        }
    }
}

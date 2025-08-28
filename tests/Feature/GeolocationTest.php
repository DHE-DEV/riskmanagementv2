<?php

declare(strict_types=1);

use App\Models\City;
use App\Models\Continent;
use App\Models\Country;
use App\Services\GeolocationService;

beforeEach(function () {
    // Erstelle Test-Daten
    $continent = Continent::factory()->create([
        'name_translations' => ['de' => 'Europa', 'en' => 'Europe'],
        'code' => 'EU',
        'lat' => 54.5260,
        'lng' => 15.2551,
    ]);

    $country = Country::factory()->create([
        'name_translations' => ['de' => 'Deutschland', 'en' => 'Germany'],
        'iso_code' => 'DE',
        'continent_id' => $continent->id,
        'lat' => 51.1657,
        'lng' => 10.4515,
    ]);

    City::factory()->create([
        'name_translations' => ['de' => 'Berlin', 'en' => 'Berlin'],
        'country_id' => $country->id,
        'lat' => 52.5200,
        'lng' => 13.4050,
        'is_capital' => true,
    ]);

    City::factory()->create([
        'name_translations' => ['de' => 'München', 'en' => 'Munich'],
        'country_id' => $country->id,
        'lat' => 48.1351,
        'lng' => 11.5820,
        'is_capital' => false,
    ]);
});

it('findet geografische Informationen basierend auf Koordinaten', function () {
    $response = $this->getJson('/api/geolocation/find-location?lat=52.5200&lng=13.4050');

    $response->assertSuccessful()
        ->assertJsonStructure([
            'success',
            'data' => [
                'coordinates',
                'city' => ['id', 'name', 'distance_km', 'is_capital'],
                'country' => ['id', 'name', 'iso_code', 'distance_km'],
                'continent' => ['id', 'name', 'code', 'distance_km'],
            ],
            'method',
        ]);

    expect($response->json('data.city.name'))->toBe('Berlin');
    expect($response->json('data.country.name'))->toBe('Deutschland');
    expect($response->json('data.continent.name'))->toBe('Europa');
});

it('findet die nächstgelegene Stadt', function () {
    $response = $this->getJson('/api/geolocation/nearest-city?lat=52.5200&lng=13.4050');

    $response->assertSuccessful()
        ->assertJsonStructure([
            'success',
            'data' => [
                'city' => [
                    'id',
                    'name',
                    'is_capital',
                    'country' => ['id', 'name', 'iso_code'],
                    'continent' => ['id', 'name', 'code'],
                ],
            ],
        ]);

    expect($response->json('data.city.name'))->toBe('Berlin');
    expect($response->json('data.city.is_capital'))->toBeTrue();
});

it('findet Städte innerhalb eines Radius', function () {
    $response = $this->getJson('/api/geolocation/cities-in-radius?lat=52.5200&lng=13.4050&radius_km=1000');

    $response->assertSuccessful()
        ->assertJsonStructure([
            'success',
            'data' => [
                'cities' => [
                    '*' => [
                        'id',
                        'name',
                        'distance_km',
                        'is_capital',
                        'country' => ['id', 'name', 'iso_code'],
                    ],
                ],
                'total_count',
            ],
        ]);

    expect($response->json('data.total_count'))->toBeGreaterThan(0);
});

it('validiert Koordinaten-Parameter', function () {
    $response = $this->getJson('/api/geolocation/find-location?lat=invalid&lng=13.4050');

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['lat']);
});

it('gibt 404 zurück wenn keine Stadt im Radius gefunden wird', function () {
    $response = $this->getJson('/api/geolocation/nearest-city?lat=0&lng=0&max_distance_km=1');

    $response->assertStatus(404)
        ->assertJson([
            'success' => false,
            'message' => 'Keine Stadt innerhalb der angegebenen Entfernung gefunden',
        ]);
});

it('testet die Geolocation-Services', function () {
    $response = $this->getJson('/api/geolocation/test');

    $response->assertSuccessful()
        ->assertJsonStructure([
            'success',
            'test_coordinates',
            'results' => [
                'database',
                'nominatim',
            ],
        ]);
});

it('verwendet verschiedene Methoden für die Standortsuche', function () {
    // Datenbank-Methode
    $response = $this->getJson('/api/geolocation/find-location?lat=52.5200&lng=13.4050&method=database');
    $response->assertSuccessful();
    expect($response->json('method'))->toBe('database');

    // Nominatim-Methode (falls verfügbar)
    $response = $this->getJson('/api/geolocation/find-location?lat=52.5200&lng=13.4050&method=nominatim');
    $response->assertSuccessful();
    expect($response->json('method'))->toBe('nominatim');
});

it('berechnet Entfernungen korrekt', function () {
    $geolocationService = app(GeolocationService::class);

    // Berlin zu München (ca. 585 km)
    $berlinLat = 52.5200;
    $berlinLng = 13.4050;
    $munichLat = 48.1351;
    $munichLng = 11.5820;

    $distance = $geolocationService->findLocationInfo($berlinLat, $berlinLng);

    // Die Entfernung sollte in einem realistischen Bereich liegen
    expect($distance['city']['distance_km'])->toBeLessThan(50); // Berlin sollte sehr nah sein
});

it('findet Kontinente basierend auf Koordinaten', function () {
    $geolocationService = app(GeolocationService::class);

    // Europäische Koordinaten
    $result = $geolocationService->findNearestContinent(52.5200, 13.4050);

    expect($result)->not->toBeNull();
    expect($result->code)->toBe('EU');
    expect($result->getName('de'))->toBe('Europa');
});

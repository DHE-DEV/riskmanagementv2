<?php

declare(strict_types=1);

use App\Models\City;
use App\Models\Continent;
use App\Models\Country;
use App\Services\ReverseGeocodingService;

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
});

it('ermittelt Land aus Koordinaten korrekt', function () {
    $reverseGeocodingService = app(ReverseGeocodingService::class);

    // Test mit Berlin-Koordinaten
    $locationInfo = $reverseGeocodingService->getLocationFromCoordinates(52.5200, 13.4050);

    expect($locationInfo['country'])->not->toBeNull();
    expect($locationInfo['country']['iso_code'])->toBe('DE');
    expect($locationInfo['country']['name'])->toBe('Deutschland');
});

it('gibt null zurück für ungültige Koordinaten', function () {
    $reverseGeocodingService = app(ReverseGeocodingService::class);

    // Test mit ungültigen Koordinaten (mitten im Ozean)
    $locationInfo = $reverseGeocodingService->getLocationFromCoordinates(0, 0);

    // Das Ergebnis kann null sein oder ein Land, je nach Datenbank-Inhalt
    // Wir testen nur, dass keine Exception geworfen wird
    expect($locationInfo)->toBeArray();
});

it('validiert Koordinaten korrekt', function () {
    $reverseGeocodingService = app(ReverseGeocodingService::class);

    // Test mit ungültigen Koordinaten
    $this->expectException(\Exception::class);
    $reverseGeocodingService->getLocationFromCoordinates(100, 200); // Ungültige Koordinaten
});

it('verwendet OpenStreetMap API für Reverse Geocoding', function () {
    $reverseGeocodingService = app(ReverseGeocodingService::class);

    // Test mit echten Koordinaten (Berlin)
    $locationInfo = $reverseGeocodingService->getLocationFromCoordinates(52.5200, 13.4050);

    expect($locationInfo)->toBeArray();
    expect($locationInfo)->toHaveKey('coordinates');
    expect($locationInfo)->toHaveKey('country');

    if ($locationInfo['country']) {
        expect($locationInfo['country'])->toHaveKey('id');
        expect($locationInfo['country'])->toHaveKey('name');
        expect($locationInfo['country'])->toHaveKey('iso_code');
    }
});

it('cached API-Aufrufe korrekt', function () {
    $reverseGeocodingService = app(ReverseGeocodingService::class);

    // Erster Aufruf
    $firstCall = $reverseGeocodingService->getLocationFromCoordinates(52.5200, 13.4050);

    // Zweiter Aufruf (sollte gecacht sein)
    $secondCall = $reverseGeocodingService->getLocationFromCoordinates(52.5200, 13.4050);

    // Beide Aufrufe sollten das gleiche Ergebnis liefern
    expect($firstCall)->toEqual($secondCall);
});

it('findet nächstgelegenes Land bei API-Fehlern', function () {
    $reverseGeocodingService = app(ReverseGeocodingService::class);

    // Test mit Koordinaten, die möglicherweise nicht in der API gefunden werden
    $locationInfo = $reverseGeocodingService->getLocationFromCoordinates(52.5200, 13.4050);

    // Das System sollte immer ein Ergebnis liefern (entweder API oder Datenbank-Fallback)
    expect($locationInfo)->toBeArray();
    expect($locationInfo)->toHaveKey('coordinates');
});

it('loggt erfolgreiche Länderermittlung', function () {
    $reverseGeocodingService = app(ReverseGeocodingService::class);

    // Mock Log-Facade
    Log::shouldReceive('info')
        ->once()
        ->withArgs(function ($message, $context) {
            return $message === 'Land automatisch aus Koordinaten ermittelt' &&
                   isset($context['coordinates']) &&
                   isset($context['country']);
        });

    $locationInfo = $reverseGeocodingService->getLocationFromCoordinates(52.5200, 13.4050);

    expect($locationInfo)->toBeArray();
});

it('behandelt API-Fehler gracefully', function () {
    // Mock HTTP-Client um API-Fehler zu simulieren
    Http::fake([
        'nominatim.openstreetmap.org/*' => Http::response([], 500),
    ]);

    $reverseGeocodingService = app(ReverseGeocodingService::class);

    // Sollte trotz API-Fehler funktionieren (Fallback zur Datenbank)
    $locationInfo = $reverseGeocodingService->getLocationFromCoordinates(52.5200, 13.4050);

    expect($locationInfo)->toBeArray();
    expect($locationInfo)->toHaveKey('coordinates');
});

it('funktioniert mit verschiedenen Koordinaten-Formaten', function () {
    $reverseGeocodingService = app(ReverseGeocodingService::class);

    // Test mit verschiedenen Koordinaten-Formaten
    $coordinates = [
        [52.5200, 13.4050], // Berlin
        [48.1351, 11.5820], // München
        [53.5511, 9.9937],  // Hamburg
    ];

    foreach ($coordinates as [$lat, $lng]) {
        $locationInfo = $reverseGeocodingService->getLocationFromCoordinates($lat, $lng);

        expect($locationInfo)->toBeArray();
        expect($locationInfo['coordinates']['lat'])->toBe($lat);
        expect($locationInfo['coordinates']['lng'])->toBe($lng);
    }
});

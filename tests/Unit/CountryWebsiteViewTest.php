<?php

use App\Http\Controllers\AdminV2\CountryWebsiteController;
use App\Models\Country;
use Illuminate\Support\Facades\Route;

// DB-frei: Die Website-Ansicht eines Landes rendert mit Beispieldaten im Format von GET /v1/countries/{code}.
uses(Tests\AppTestCase::class);

it('is an admin-only route', function () {
    $route = Route::getRoutes()->getByName('adminv2.master-data.countries.website');

    expect($route)->not->toBeNull()
        ->and($route->uri())->toBe('adminv2/master-data/countries/{country}/website')
        ->and($route->gatherMiddleware())->toContain('adminv2');
});

it('renders every tab with the api data', function () {
    $country = (new Country)->forceFill(['id' => 14, 'iso_code' => 'ES']);
    $html = view('adminv2.country-website', [
        'country' => $country,
        'lang' => 'de',
        'languages' => CountryWebsiteController::LANGUAGES,
        'c' => [
            'iso_code' => 'ES', 'name' => 'Spanien', 'continent' => ['code' => 'EU', 'name' => 'Europa'],
            'membership' => ['eu' => true, 'schengen' => true],
            'currency' => ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€'],
            'capital' => ['id' => 1, 'name' => 'Madrid', 'lat' => 40.4, 'lng' => -3.7],
            'description' => ['short' => 'Kurz', 'long' => 'Lang', 'known_for' => ['Tapas']],
            'travel_info' => ['emergency' => ['general' => '112'], 'driving_side_label' => 'Rechtsverkehr', 'religions' => []],
            'power' => ['voltage' => 230, 'frequency' => 50, 'plug_types' => [['type' => 'F', 'description' => 'Schuko', 'image' => '/f.svg']]],
            'tipping' => ['taxi' => ['from' => 0, 'to' => 5, 'unit' => 'percent', 'description' => 'Aufrunden']],
            'holidays' => ['year' => 2026, 'items' => [['date' => '2026-12-25', 'weekday' => 5, 'name' => 'Weihnachten', 'is_national' => true]]],
            'airlines' => [['name' => 'Iberia', 'iata_code' => 'IB']],
            'mobile_operators' => [['name' => 'Movistar', 'offers_esim' => true]],
            'images' => ['hero_url' => null, 'gallery' => []],
            'risk_profile' => ['overall' => ['level' => 2, 'label' => 'Gering'], 'categories' => [
                'entry' => ['label' => 'Einreise', 'fields' => ['visa' => ['name' => 'Visum nötig', 'type' => 'bool', 'value' => false, 'note' => 'Für EU-Bürger nicht.']]],
            ]],
        ],
        'regions' => [['id' => 1, 'name' => 'Andalusien', 'is_popular' => true, 'cities_count' => 3]],
        'subregions' => [['id' => 9, 'region' => ['id' => 1], 'kind' => 'area', 'name' => 'Costa del Sol']],
        'cities' => [['id' => 2, 'name' => 'Sevilla', 'region' => ['id' => 1, 'name' => 'Andalusien'], 'is_popular' => true]],
        'sights' => [['id' => 3, 'name' => 'Alhambra', 'category' => 'palace', 'category_name' => 'Palast', 'is_highlight' => true, 'region' => ['id' => 1, 'name' => 'Andalusien']]],
        'weather' => null,
        'rates' => null,
    ])->render();

    expect($html)
        ->toContain('Spanien')->toContain('Tapas')->toContain('Madrid')->toContain('Weihnachten')
        ->toContain('Andalusien')->toContain('Costa del Sol')->toContain('Sevilla')->toContain('Alhambra')
        ->toContain('Iberia')->toContain('Movistar')->toContain('112')->toContain('0–5 %')
        ->toContain('Für EU-Bürger nicht.')->toContain('Risiko: Gering')
        ->toContain('Aktuelle Wetterdaten sind gerade nicht verfügbar.')
        ->toContain('data-panel="health"');
});

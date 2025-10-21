<?php

use App\Http\Controllers\AirportSearchController;
use App\Http\Controllers\Api\EntryConditionsController;
use App\Http\Controllers\CustomEventController;
use App\Http\Controllers\GdacsController;
use App\Http\Controllers\GeolocationController;
use App\Http\Controllers\SocialLinkController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// GDACS API Routes
Route::prefix('gdacs')->group(function () {
    Route::get('/fetch-events', [GdacsController::class, 'fetchEvents'])->name('gdacs.fetch-events');
    Route::get('/dashboard-events', [GdacsController::class, 'getDashboardEvents'])->name('gdacs.dashboard-events');
    Route::get('/statistics', [GdacsController::class, 'getStatistics'])->name('gdacs.statistics');
    Route::post('/clear-cache', [GdacsController::class, 'clearCache'])->name('gdacs.clear-cache');
    Route::get('/test-apis', [GdacsController::class, 'testApis'])->name('gdacs.test-apis');

    // Wetter und Zeitzonen APIs
    Route::post('/event-details', [GdacsController::class, 'getEventDetails'])->name('gdacs.event-details');
    Route::post('/weather-for-events', [GdacsController::class, 'getWeatherForEvents'])->name('gdacs.weather-for-events');
});

// Custom Events API Routes
Route::prefix('custom-events')->group(function () {
    Route::get('/dashboard-events', [CustomEventController::class, 'getDashboardEvents'])->name('custom-events.dashboard-events');
    Route::get('/map-events', [CustomEventController::class, 'getMapEvents'])->name('custom-events.map-events');
    Route::get('/statistics', [CustomEventController::class, 'getStatistics'])->name('custom-events.statistics');
    Route::get('/event-types', [CustomEventController::class, 'getEventTypes'])->name('custom-events.event-types');
    Route::post('/track-click', [CustomEventController::class, 'trackClick'])->name('custom-events.track-click');
    Route::get('/{eventId}/click-statistics', [CustomEventController::class, 'getClickStatistics'])->name('custom-events.click-statistics');
    Route::get('/{eventId}', [CustomEventController::class, 'getEvent'])->name('custom-events.get-event');
});

// Continents API
Route::get('/continents', function () {
    return response()->json([
        'success' => true,
        'data' => \App\Models\Continent::orderBy('sort_order')->get(['id', 'code', 'name_translations', 'sort_order'])->map(function ($continent) {
            return [
                'id' => $continent->id,
                'code' => $continent->code,
                'name' => $continent->getName('de'),
                'name_en' => $continent->getName('en'),
                'sort_order' => $continent->sort_order,
            ];
        })
    ]);
})->name('continents.list');

// Airports search
Route::get('/airports/search', [AirportSearchController::class, 'search'])->name('airports.search');
Route::get('/airports/countries', [AirportSearchController::class, 'countries'])->name('airports.countries');
Route::get('/airports/continents', [AirportSearchController::class, 'continents'])->name('airports.continents');
Route::get('/countries/search', [AirportSearchController::class, 'countrySearch'])->name('countries.search');
Route::get('/countries/search-debug', [AirportSearchController::class, 'countrySearchDebug'])->name('countries.search-debug');
Route::get('/countries/mappings', [AirportSearchController::class, 'getCountryMappings'])->name('countries.mappings');
Route::get('/countries/locate', [AirportSearchController::class, 'countryLocate'])->name('countries.locate');

// Social links
Route::get('/social-links', [SocialLinkController::class, 'index'])->name('social-links.index');

// Geolocation API Routes
Route::prefix('geolocation')->group(function () {
    Route::get('/find-location', [GeolocationController::class, 'findLocation'])->name('geolocation.find-location');
    Route::get('/nearest-city', [GeolocationController::class, 'findNearestCity'])->name('geolocation.nearest-city');
    Route::get('/cities-in-radius', [GeolocationController::class, 'findCitiesInRadius'])->name('geolocation.cities-in-radius');
    Route::get('/test', [GeolocationController::class, 'test'])->name('geolocation.test');
});

// Entry Conditions API Routes
Route::prefix('entry-conditions')->group(function () {
    Route::get('/countries', [EntryConditionsController::class, 'getCountries'])->name('entry-conditions.countries');
    Route::post('/search', [EntryConditionsController::class, 'search'])->name('entry-conditions.search');
});

// Countries GeoJSON Route
Route::get('/countries-geojson', function () {
    $path = storage_path('app/private/countries.geojson');

    if (!file_exists($path)) {
        return response()->json(['error' => 'GeoJSON file not found'], 404);
    }

    return response()->file($path, [
        'Content-Type' => 'application/geo+json',
        'Cache-Control' => 'public, max-age=86400' // Cache for 24 hours
    ]);
})->name('countries.geojson');

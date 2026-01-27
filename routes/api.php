<?php

use App\Http\Controllers\AirportSearchController;
use App\Http\Controllers\Api\EntryConditionsController;
use App\Http\Controllers\Api\Plugin\HandshakeController;
use App\Http\Controllers\Api\ShareLinkController;
use App\Http\Controllers\Api\V1\ProximityController;
use App\Http\Controllers\Api\V1\ShareLinkController as V1ShareLinkController;
use App\Http\Controllers\Api\V1\TripController;
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
        }),
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
// Using web middleware to support session-based customer authentication
Route::prefix('entry-conditions')->middleware('web')->group(function () {
    Route::get('/countries', [EntryConditionsController::class, 'getCountries'])->name('entry-conditions.countries');
    Route::get('/all-coordinates', [EntryConditionsController::class, 'getAllCountryCoordinates'])->name('entry-conditions.all-coordinates');
    Route::post('/search', [EntryConditionsController::class, 'search'])->name('entry-conditions.search');
    Route::post('/content', [EntryConditionsController::class, 'getContent'])->name('entry-conditions.content');
    Route::get('/details', [EntryConditionsController::class, 'getDetails'])->name('entry-conditions.details');
    Route::get('/pdf', [EntryConditionsController::class, 'getPDF'])->name('entry-conditions.pdf');
});

// Booking Locations API Routes
Route::prefix('booking-locations')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\BookingLocationController::class, 'index'])->name('booking-locations.index');
    Route::post('/search', [\App\Http\Controllers\Api\BookingLocationController::class, 'search'])->name('booking-locations.search');
});

// Countries GeoJSON Route
Route::get('/countries-geojson', function () {
    $path = storage_path('app/private/countries.geojson');

    if (! file_exists($path)) {
        return response()->json(['error' => 'GeoJSON file not found'], 404);
    }

    return response()->file($path, [
        'Content-Type' => 'application/geo+json',
        'Cache-Control' => 'public, max-age=86400', // Cache for 24 hours
    ]);
})->name('countries.geojson');

// Cruise Search API Routes
Route::prefix('cruise-search')->group(function () {
    Route::get('/cruise-lines', [\App\Http\Controllers\Api\CruiseSearchController::class, 'getCruiseLines'])->name('cruise-search.cruise-lines');
    Route::get('/ships', [\App\Http\Controllers\Api\CruiseSearchController::class, 'getShips'])->name('cruise-search.ships');
    Route::get('/routes', [\App\Http\Controllers\Api\CruiseSearchController::class, 'getRoutes'])->name('cruise-search.routes');
    Route::get('/cruise-dates', [\App\Http\Controllers\Api\CruiseSearchController::class, 'getCruiseDates'])->name('cruise-search.cruise-dates');
    Route::post('/search', [\App\Http\Controllers\Api\CruiseSearchController::class, 'search'])->name('cruise-search.search');
});

/*
|--------------------------------------------------------------------------
| API v1 Routes
|--------------------------------------------------------------------------
*/
Route::prefix('v1')->group(function () {
    // Share Links API (public)
    Route::prefix('share-links')->group(function () {
        Route::get('/', [ShareLinkController::class, 'index'])->name('v1.share-links.index');
        Route::post('/', [ShareLinkController::class, 'store'])->name('v1.share-links.store');
        Route::get('/{token}', [ShareLinkController::class, 'show'])->name('v1.share-links.show');
        Route::delete('/{token}', [ShareLinkController::class, 'destroy'])->name('v1.share-links.destroy');
    });
});

/*
|--------------------------------------------------------------------------
| Travel Detail API Routes (v1 - Protected)
|--------------------------------------------------------------------------
|
| These routes handle trip import, management, and proximity queries.
| Protected by Sanctum token authentication.
|
*/
Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {
    // Trip Management
    Route::prefix('trips')->group(function () {
        Route::get('/', [TripController::class, 'index'])->name('v1.trips.index');
        Route::post('/', [TripController::class, 'store'])->name('v1.trips.store');
        Route::get('/{trip}', [TripController::class, 'show'])->name('v1.trips.show');
        Route::delete('/{trip}', [TripController::class, 'destroy'])->name('v1.trips.destroy');
        Route::get('/{trip}/summary', [TripController::class, 'summary'])->name('v1.trips.summary');
        Route::post('/{trip}/share-link', [TripController::class, 'generateShareLink'])->name('v1.trips.share-link');
    });

    // Direct Share-Link Generation (without database storage) - V1 Controller
    Route::post('/td-share-links', [V1ShareLinkController::class, 'store'])->name('v1.td-share-links.store');

    // Proximity Queries
    Route::prefix('proximity')->group(function () {
        Route::post('/near-event', [ProximityController::class, 'nearEvent'])->name('v1.proximity.near-event');
        Route::post('/at-location', [ProximityController::class, 'atLocation'])->name('v1.proximity.at-location');
        Route::post('/affected-by-event/{event}', [ProximityController::class, 'affectedByEvent'])->name('v1.proximity.affected-by-event');
        Route::post('/trips-in-country', [ProximityController::class, 'tripsInCountry'])->name('v1.proximity.trips-in-country');
    });
});

/*
|--------------------------------------------------------------------------
| Folder Management API Routes (Customer-Protected)
|--------------------------------------------------------------------------
|
| These routes handle folder/trip management for travel agencies.
| Protected by customer authentication.
|
*/
Route::prefix('customer/folders')->middleware(['auth:sanctum'])->group(function () {
    // Folder CRUD
    Route::get('/', [\App\Http\Controllers\Api\FolderApiController::class, 'index'])->name('customer.folders.index');
    Route::get('/{id}', [\App\Http\Controllers\Api\FolderApiController::class, 'show'])->name('customer.folders.show');

    // Map locations
    Route::get('/map-locations', [\App\Http\Controllers\Api\FolderApiController::class, 'getMapLocations'])->name('customer.folders.map-locations');

    // Proximity queries
    Route::post('/near-point', [\App\Http\Controllers\Api\FolderApiController::class, 'getTravelersNearPoint'])->name('customer.folders.near-point');
    Route::post('/in-country', [\App\Http\Controllers\Api\FolderApiController::class, 'getTravelersInCountry'])->name('customer.folders.in-country');
    Route::post('/affected-folders', [\App\Http\Controllers\Api\FolderApiController::class, 'getAffectedFolders'])->name('customer.folders.affected');
    Route::get('/statistics', [\App\Http\Controllers\Api\FolderApiController::class, 'getTravelerStatistics'])->name('customer.folders.statistics');

    // Import functionality
    Route::post('/import', [\App\Http\Controllers\Api\FolderImportController::class, 'import'])->name('customer.folders.import');
    Route::get('/imports', [\App\Http\Controllers\Api\FolderImportController::class, 'listImports'])->name('customer.folders.imports.list');
    Route::get('/imports/{logId}/status', [\App\Http\Controllers\Api\FolderImportController::class, 'getImportStatus'])->name('customer.folders.imports.status');
});

/*
|--------------------------------------------------------------------------
| Plugin API Routes
|--------------------------------------------------------------------------
|
| Widget handshake endpoint for license validation and usage tracking.
| Rate limited to 60 requests per minute per IP.
| CORS enabled for cross-domain widget embedding.
|
*/
Route::prefix('plugin')->group(function () {
    Route::post('/handshake', HandshakeController::class)
        ->middleware(['throttle:60,1'])
        ->name('plugin.handshake');
});

/*
|--------------------------------------------------------------------------
| GTM API Routes (Customer-Protected)
|--------------------------------------------------------------------------
|
| Global Travel Monitor JSON API for customers.
| Protected by Sanctum token authentication with gtm:read ability.
|
*/
Route::prefix('v1/gtm')->middleware([
    'auth:sanctum',
    \App\Http\Middleware\GtmApiAuthenticate::class,
    \App\Http\Middleware\GtmApiRequestLogger::class,
    'throttle:gtm-api',
])->group(function () {
    Route::get('/events', [\App\Http\Controllers\Api\V1\GtmApiController::class, 'index'])->name('v1.gtm.events.index');
    Route::get('/events/{id}', [\App\Http\Controllers\Api\V1\GtmApiController::class, 'show'])->name('v1.gtm.events.show');
    Route::get('/countries', [\App\Http\Controllers\Api\V1\GtmApiController::class, 'countries'])->name('v1.gtm.countries');
});

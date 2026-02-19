<?php

/*
|--------------------------------------------------------------------------
| API Subdomain Routes
|--------------------------------------------------------------------------
|
| These routes are registered on the API subdomain (api.global-travel-monitor.eu)
| and mirror the external-facing API routes without the /api prefix.
|
| Subdomain:  api.global-travel-monitor.eu/v1/events
| Main domain: global-travel-monitor.eu/api/v1/events  (still works)
|
*/

/*
|--------------------------------------------------------------------------
| Root & Fallback
|--------------------------------------------------------------------------
*/
$apiInfo = [
    'name' => 'Passolution API',
    'version' => 'v1',
    'endpoints' => [
        'Event API' => [
            'GET /v1/events' => 'Events auflisten',
            'POST /v1/events' => 'Event erstellen',
            'GET /v1/events/{uuid}' => 'Event anzeigen',
            'PUT /v1/events/{uuid}' => 'Event aktualisieren',
            'DELETE /v1/events/{uuid}' => 'Event löschen',
        ],
        'Referenzdaten' => [
            'GET /v1/event-types' => 'Verfügbare Event-Typen',
            'GET /v1/countries' => 'Verfügbare Länder',
        ],
        'GTM API' => [
            'GET /v1/gtm/events' => 'Aktive Events auflisten',
            'GET /v1/gtm/events/{id}' => 'Event anzeigen',
            'GET /v1/gtm/countries' => 'Länder mit aktiven Events',
        ],
    ],
    'authentication' => 'Bearer Token via Authorization header',
];

Route::get('/', fn () => response()->json($apiInfo))->name('sub.root');
Route::get('/v1', fn () => response()->json($apiInfo))->name('sub.v1.root');

Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'Endpoint not found. See / for available endpoints.',
    ], 404);
})->name('sub.fallback');

use App\Http\Controllers\Api\V1\EventApiController;
use App\Http\Controllers\Api\V1\EventReferenceController;
use App\Http\Controllers\Api\V1\GtmApiController;
use App\Http\Middleware\ApiClientAuthenticate;
use App\Http\Middleware\ApiClientRequestLogger;
use App\Http\Middleware\GtmApiAuthenticate;
use App\Http\Middleware\GtmApiRequestLogger;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Event API Routes
|--------------------------------------------------------------------------
*/
Route::prefix('v1/events')->middleware([
    'auth:sanctum',
    ApiClientAuthenticate::class,
    ApiClientRequestLogger::class,
    'throttle:api-client',
])->group(function () {
    Route::get('/', [EventApiController::class, 'index'])->name('sub.v1.events.index');
    Route::post('/', [EventApiController::class, 'store'])->name('sub.v1.events.store');
    Route::get('/{uuid}', [EventApiController::class, 'show'])->name('sub.v1.events.show');
    Route::put('/{uuid}', [EventApiController::class, 'update'])->name('sub.v1.events.update');
    Route::delete('/{uuid}', [EventApiController::class, 'destroy'])->name('sub.v1.events.destroy');
});

// Event API Reference Data
Route::prefix('v1')->middleware([
    'auth:sanctum',
    ApiClientAuthenticate::class,
])->group(function () {
    Route::get('/event-types', [EventReferenceController::class, 'eventTypes'])->name('sub.v1.api-client.event-types');
    Route::get('/countries', [EventReferenceController::class, 'countries'])->name('sub.v1.api-client.countries');
});

/*
|--------------------------------------------------------------------------
| GTM API Routes
|--------------------------------------------------------------------------
*/
Route::prefix('v1/gtm')->middleware([
    'auth:sanctum',
    GtmApiAuthenticate::class,
    GtmApiRequestLogger::class,
    'throttle:gtm-api',
])->group(function () {
    Route::get('/events', [GtmApiController::class, 'index'])->name('sub.v1.gtm.events.index');
    Route::get('/events/{id}', [GtmApiController::class, 'show'])->name('sub.v1.gtm.events.show');
    Route::get('/countries', [GtmApiController::class, 'countries'])->name('sub.v1.gtm.countries');
});

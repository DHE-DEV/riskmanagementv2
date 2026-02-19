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
Route::get('/', function () {
    return response()->json([
        'name' => 'Passolution API',
        'documentation' => 'https://global-travel-monitor.eu/docs',
        'endpoints' => [
            'Event API' => '/v1/events',
            'GTM API' => '/v1/gtm/events',
        ],
    ]);
})->name('sub.root');

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

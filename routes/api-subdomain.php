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
| Landing Page, Docs & Fallback
|--------------------------------------------------------------------------
*/
Route::get('/', fn () => view('api.landing'))->withoutMiddleware('api')->name('sub.root');

Route::get('/v1', function () {
    return response()->json([
        'name' => 'Passolution API',
        'version' => 'v1',
        'documentation' => '/',
        'endpoints' => [
            'Event API' => '/v1/events',
            'GTM API' => '/v1/gtm/events',
            'Referenzdaten' => ['/v1/event-types', '/v1/countries'],
        ],
        'authentication' => 'Bearer Token via Authorization header',
    ]);
})->name('sub.v1.root');

// Documentation file downloads
Route::get('/docs/{file}', function (string $file) {
    $allowed = [
        'event-api-openapi.yaml',
        'event-api-guide.md',
        'gtm-api-openapi.yaml',
        'gtm-api-guide.md',
        'feed-api-openapi.yaml',
        'feed-api-guide.md',
        'folder-import-api-openapi.yaml',
        'folder-import-api-guide.md',
    ];

    if (!in_array($file, $allowed)) {
        abort(404);
    }

    $path = base_path("docs/{$file}");

    if (!file_exists($path)) {
        abort(404);
    }

    $contentType = str_ends_with($file, '.yaml') ? 'application/x-yaml' : 'text/markdown';

    return response()->file($path, [
        'Content-Type' => $contentType,
        'Content-Disposition' => "attachment; filename=\"{$file}\"",
    ]);
})->where('file', '[a-z0-9\-]+\.(yaml|md)')->name('sub.docs.download');

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

<?php

use App\Http\Controllers\AirportSearchController;
use App\Http\Controllers\Api\EntryConditionsController;
use App\Http\Controllers\Api\Plugin\HandshakeController;
use App\Http\Controllers\Api\ShareLinkController;
use App\Http\Controllers\Api\V1\EventApiController;
use App\Http\Controllers\Api\V1\EventReferenceController;
use App\Http\Controllers\Api\V1\ProximityController;
use App\Http\Controllers\Api\V1\ShareLinkController as V1ShareLinkController;
use App\Http\Controllers\Api\V1\TripController;
use App\Http\Controllers\CustomEventController;
use App\Http\Controllers\GdacsController;
use App\Http\Controllers\GeolocationController;
use App\Http\Controllers\SocialLinkController;
use App\Http\Controllers\Api\V1\BranchApiController;
use App\Http\Controllers\Api\V1\BranchContactApiController;
use App\Http\Controllers\Api\V1\CustomerSettingsController;
use App\Http\Controllers\Api\V1\DepartmentApiController;
use App\Http\Controllers\Api\V1\EmailAddressApiController;
use App\Http\Controllers\Api\V1\EmployeeApiController;
use App\Http\Controllers\Api\V1\EmployeeGroupApiController;
use App\Http\Controllers\Api\V1\OrgNodeApiController;
use App\Http\Controllers\Api\V1\PhoneNumberApiController;
use App\Http\Controllers\Api\V1\PluginDomainController;
use App\Http\Controllers\Api\V1\WebsiteApiController;
use App\Http\Middleware\ApiClientAuthenticate;
use App\Http\Middleware\ApiClientRequestLogger;
use App\Http\Middleware\AuthenticatePluginKey;
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
    // Versionshistorie: alle jemals veroeffentlichten Staende eines Ereignisses.
    Route::get('/{eventId}/versions', [CustomEventController::class, 'getVersions'])->name('custom-events.versions');
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
Route::get('/airports/{airport}/airlines', [AirportSearchController::class, 'airlines'])->name('airports.airlines');
Route::get('/airports/countries', [AirportSearchController::class, 'countries'])->name('airports.countries');
Route::get('/airports/continents', [AirportSearchController::class, 'continents'])->name('airports.continents');
Route::get('/airport-codes/search', [AirportSearchController::class, 'airportCodeSearch'])->name('airport-codes.search');
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
| Customer Settings API Routes (Customer-Protected)
|--------------------------------------------------------------------------
|
| REST API for managing customer master data, branches, contacts,
| organization structure, departments, employees, and groups.
| Protected by Sanctum token authentication.
|
*/
Route::prefix('v1/customer')->middleware(['auth:sanctum'])->group(function () {
    // Master Data (Firmendaten)
    Route::get('/settings', [CustomerSettingsController::class, 'show'])->name('v1.customer.settings.show');
    Route::put('/settings/company-address', [CustomerSettingsController::class, 'updateCompanyAddress'])->name('v1.customer.settings.company-address');
    Route::put('/settings/billing-address', [CustomerSettingsController::class, 'updateBillingAddress'])->name('v1.customer.settings.billing-address');
    Route::put('/settings/customer-type', [CustomerSettingsController::class, 'updateCustomerType'])->name('v1.customer.settings.customer-type');
    Route::put('/settings/business-type', [CustomerSettingsController::class, 'updateBusinessType'])->name('v1.customer.settings.business-type');

    // Branches (Adressen)
    Route::prefix('branches')->group(function () {
        Route::get('/', [BranchApiController::class, 'index'])->name('v1.customer.branches.index');
        Route::post('/', [BranchApiController::class, 'store'])->name('v1.customer.branches.store');
        Route::get('/{branch}', [BranchApiController::class, 'show'])->name('v1.customer.branches.show');
        Route::put('/{branch}', [BranchApiController::class, 'update'])->name('v1.customer.branches.update');
        Route::delete('/{branch}', [BranchApiController::class, 'destroy'])->name('v1.customer.branches.destroy');
        Route::post('/{branch}/cancel-deletion', [BranchApiController::class, 'cancelScheduledDeletion'])->name('v1.customer.branches.cancel-deletion');
    });

    // Phone Numbers (Rufnummern)
    Route::prefix('phone-numbers')->group(function () {
        Route::get('/', [PhoneNumberApiController::class, 'index'])->name('v1.customer.phone-numbers.index');
        Route::post('/', [PhoneNumberApiController::class, 'store'])->name('v1.customer.phone-numbers.store');
        Route::put('/{phoneNumber}', [PhoneNumberApiController::class, 'update'])->name('v1.customer.phone-numbers.update');
        Route::delete('/{phoneNumber}', [PhoneNumberApiController::class, 'destroy'])->name('v1.customer.phone-numbers.destroy');
        Route::post('/reorder', [PhoneNumberApiController::class, 'reorder'])->name('v1.customer.phone-numbers.reorder');
    });

    // Email Addresses (E-Mail-Adressen)
    Route::prefix('email-addresses')->group(function () {
        Route::get('/', [EmailAddressApiController::class, 'index'])->name('v1.customer.email-addresses.index');
        Route::post('/', [EmailAddressApiController::class, 'store'])->name('v1.customer.email-addresses.store');
        Route::put('/{emailAddress}', [EmailAddressApiController::class, 'update'])->name('v1.customer.email-addresses.update');
        Route::delete('/{emailAddress}', [EmailAddressApiController::class, 'destroy'])->name('v1.customer.email-addresses.destroy');
        Route::post('/reorder', [EmailAddressApiController::class, 'reorder'])->name('v1.customer.email-addresses.reorder');
    });

    // Websites (Web)
    Route::prefix('websites')->group(function () {
        Route::get('/', [WebsiteApiController::class, 'index'])->name('v1.customer.websites.index');
        Route::post('/', [WebsiteApiController::class, 'store'])->name('v1.customer.websites.store');
        Route::put('/{website}', [WebsiteApiController::class, 'update'])->name('v1.customer.websites.update');
        Route::delete('/{website}', [WebsiteApiController::class, 'destroy'])->name('v1.customer.websites.destroy');
        Route::post('/reorder', [WebsiteApiController::class, 'reorder'])->name('v1.customer.websites.reorder');
    });

    // Branch Contacts (Ansprechpartner pro Adresse)
    Route::prefix('branch-contacts')->group(function () {
        Route::get('/', [BranchContactApiController::class, 'index'])->name('v1.customer.branch-contacts.index');
        Route::post('/', [BranchContactApiController::class, 'store'])->name('v1.customer.branch-contacts.store');
        Route::put('/{branchContact}', [BranchContactApiController::class, 'update'])->name('v1.customer.branch-contacts.update');
        Route::delete('/{branchContact}', [BranchContactApiController::class, 'destroy'])->name('v1.customer.branch-contacts.destroy');
    });

    // Organization Structure (Organisationsstruktur)
    Route::prefix('org-nodes')->group(function () {
        Route::get('/', [OrgNodeApiController::class, 'index'])->name('v1.customer.org-nodes.index');
        Route::post('/', [OrgNodeApiController::class, 'store'])->name('v1.customer.org-nodes.store');
        Route::get('/{orgNode}', [OrgNodeApiController::class, 'show'])->name('v1.customer.org-nodes.show');
        Route::put('/{orgNode}', [OrgNodeApiController::class, 'update'])->name('v1.customer.org-nodes.update');
        Route::delete('/{orgNode}', [OrgNodeApiController::class, 'destroy'])->name('v1.customer.org-nodes.destroy');
        Route::post('/reorder', [OrgNodeApiController::class, 'reorder'])->name('v1.customer.org-nodes.reorder');
        Route::post('/{orgNode}/move', [OrgNodeApiController::class, 'move'])->name('v1.customer.org-nodes.move');
    });

    // Departments (Abteilungen)
    Route::prefix('departments')->group(function () {
        Route::get('/', [DepartmentApiController::class, 'index'])->name('v1.customer.departments.index');
        Route::post('/', [DepartmentApiController::class, 'store'])->name('v1.customer.departments.store');
        Route::put('/{department}', [DepartmentApiController::class, 'update'])->name('v1.customer.departments.update');
        Route::delete('/{department}', [DepartmentApiController::class, 'destroy'])->name('v1.customer.departments.destroy');
        Route::post('/reorder', [DepartmentApiController::class, 'reorder'])->name('v1.customer.departments.reorder');
    });

    // Employees (Benutzer)
    Route::prefix('employees')->group(function () {
        Route::get('/', [EmployeeApiController::class, 'index'])->name('v1.customer.employees.index');
        Route::post('/', [EmployeeApiController::class, 'store'])->name('v1.customer.employees.store');
        Route::get('/{employee}', [EmployeeApiController::class, 'show'])->name('v1.customer.employees.show');
        Route::put('/{employee}', [EmployeeApiController::class, 'update'])->name('v1.customer.employees.update');
        Route::delete('/{employee}', [EmployeeApiController::class, 'destroy'])->name('v1.customer.employees.destroy');
    });

    // Employee Groups (Benutzergruppen)
    Route::prefix('employee-groups')->group(function () {
        Route::get('/', [EmployeeGroupApiController::class, 'index'])->name('v1.customer.employee-groups.index');
        Route::post('/', [EmployeeGroupApiController::class, 'store'])->name('v1.customer.employee-groups.store');
        Route::get('/{employeeGroup}', [EmployeeGroupApiController::class, 'show'])->name('v1.customer.employee-groups.show');
        Route::put('/{employeeGroup}', [EmployeeGroupApiController::class, 'update'])->name('v1.customer.employee-groups.update');
        Route::delete('/{employeeGroup}', [EmployeeGroupApiController::class, 'destroy'])->name('v1.customer.employee-groups.destroy');
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
Route::prefix('v1/folders')->middleware([
    'auth:sanctum',
    \App\Http\Middleware\EnsureCustomerApiToken::class,
])->group(function () {
    // Folder CRUD ({id} is registered last so it cannot shadow the literal routes below)
    Route::get('/', [\App\Http\Controllers\Api\FolderApiController::class, 'index'])->name('customer.folders.index');

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

    // Wildcard last: everything above would otherwise be swallowed by /{id}
    Route::get('/{id}', [\App\Http\Controllers\Api\FolderApiController::class, 'show'])->name('customer.folders.show');
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
| Plugin Domain Management API Routes
|--------------------------------------------------------------------------
|
| REST API for plugin clients to manage their allowed domains.
| Authenticated via plugin key (pk_live_*) as Bearer Token.
|
*/
Route::prefix('v1/plugin/gtm/domains')->middleware([
    AuthenticatePluginKey::class,
    'throttle:plugin-api',
])->group(function () {
    Route::get('/', [PluginDomainController::class, 'index'])->name('v1.plugin.gtm.domains.index');
    Route::post('/', [PluginDomainController::class, 'store'])->name('v1.plugin.gtm.domains.store');
    Route::post('/bulk', [PluginDomainController::class, 'bulkStore'])->name('v1.plugin.gtm.domains.bulk-store');
    Route::delete('/bulk', [PluginDomainController::class, 'bulkDestroy'])->name('v1.plugin.gtm.domains.bulk-destroy');
    Route::get('/{uuid}', [PluginDomainController::class, 'show'])->name('v1.plugin.gtm.domains.show');
    Route::put('/{uuid}', [PluginDomainController::class, 'update'])->name('v1.plugin.gtm.domains.update');
    Route::delete('/{uuid}', [PluginDomainController::class, 'destroy'])->name('v1.plugin.gtm.domains.destroy');
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
/*
|--------------------------------------------------------------------------
| Events API Routes (Customer-Protected, Read-Only)
|--------------------------------------------------------------------------
|
| Read-only access to all active events from all providers.
| Supports filtering by risk_level, country, event_category, region, and source.
| Protected by Sanctum token authentication with GTM API permissions.
|
*/
Route::prefix('v1')->middleware([
    'auth:sanctum',
    \App\Http\Middleware\GtmApiAuthenticate::class,
    \App\Http\Middleware\GtmApiRequestLogger::class,
    'throttle:gtm-api',
])->group(function () {
    // Events
    Route::get('/events', [\App\Http\Controllers\Api\V1\GtmApiController::class, 'index'])->name('v1.events.index');
    Route::get('/events/countries', [\App\Http\Controllers\Api\V1\GtmApiController::class, 'countriesWithEvents'])->name('v1.events.countries');
    Route::get('/events/nearby', [\App\Http\Controllers\Api\V1\GtmApiController::class, 'nearby'])->name('v1.events.nearby');
    Route::get('/events/{id}', [\App\Http\Controllers\Api\V1\GtmApiController::class, 'show'])->name('v1.events.show');

    // Basisdaten
    Route::get('/continents', [\App\Http\Controllers\Api\V1\BaseDataController::class, 'continents'])->name('v1.continents');
    Route::get('/countries', [\App\Http\Controllers\Api\V1\BaseDataController::class, 'countries'])->name('v1.countries');
    Route::get('/regions', [\App\Http\Controllers\Api\V1\BaseDataController::class, 'regions'])->name('v1.regions');
    Route::get('/event-categories', [\App\Http\Controllers\Api\V1\BaseDataController::class, 'eventCategories'])->name('v1.event-categories');
});

/*
|--------------------------------------------------------------------------
| Custom Event API Routes (API-Client-Protected)
|--------------------------------------------------------------------------
|
| REST API for external API partners to create and manage their own events.
| Protected by Sanctum token authentication with API client validation.
|
*/
Route::prefix('v1/custom/events')->middleware([
    'auth:sanctum',
    ApiClientAuthenticate::class,
    ApiClientRequestLogger::class,
    'throttle:api-client',
])->group(function () {
    Route::get('/', [EventApiController::class, 'index'])->name('v1.custom.events.index');
    Route::get('/nearby', [EventApiController::class, 'nearby'])->name('v1.custom.events.nearby');
    Route::post('/', [EventApiController::class, 'store'])->name('v1.custom.events.store');
    Route::get('/{uuid}', [EventApiController::class, 'show'])->name('v1.custom.events.show');
    Route::put('/{uuid}', [EventApiController::class, 'update'])->name('v1.custom.events.update');
    Route::delete('/{uuid}', [EventApiController::class, 'destroy'])->name('v1.custom.events.destroy');
});

// Custom Event API Reference Data (API-Client-Protected, read-only)
Route::prefix('v1/custom')->middleware([
    'auth:sanctum',
    ApiClientAuthenticate::class,
])->group(function () {
    Route::get('/event-categories', [EventReferenceController::class, 'eventCategories'])->name('v1.custom.event-categories');
    Route::get('/countries', [EventReferenceController::class, 'countries'])->name('v1.custom.countries');
});

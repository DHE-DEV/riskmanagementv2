<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GdacsController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\EventFeedController;
use App\Http\Controllers\CountryFeedController;

Route::get('/', function () {
    $eventId = request()->query('event');
    $sharedEvent = null;

    if ($eventId) {
        $sharedEvent = \App\Models\CustomEvent::with(['countries', 'eventType', 'eventTypes'])
            ->where('is_active', true)
            ->find($eventId);
    }

    return view('livewire.pages.dashboard', [
        'sharedEvent' => $sharedEvent,
    ]);
})->name('home');

// Debug route to check auth status
Route::get('/auth-debug', function () {
    return response()->json([
        'customer_authenticated' => auth('customer')->check(),
        'customer_user' => auth('customer')->user(),
        'session_id' => session()->getId(),
        'has_session' => session()->has('_token'),
        'all_guards' => [
            'web' => auth('web')->check(),
            'customer' => auth('customer')->check(),
        ],
    ]);
});

Route::get('/entry-conditions', function () {
    if (!config('app.entry_conditions_enabled', true)) {
        abort(404);
    }
    return view('livewire.pages.entry-conditions');
})->name('entry-conditions');

// RSS/Atom Feed Routes
// Struktur: /feed/{kategorie}/...
// Ermöglicht spätere Erweiterung um weitere Feed-Kategorien (news, advisories, topics, etc.)
Route::prefix('feed')->name('feed.')->group(function () {

    // Event-Feeds: /feed/events/...
    Route::prefix('events')->name('events.')->group(function () {
        // All events feeds
        Route::get('all.xml', [EventFeedController::class, 'allEvents'])->name('all.rss');
        Route::get('all.atom', [EventFeedController::class, 'allEventsAtom'])->name('all.atom');

        // Events by priority (high, medium, low, info)
        Route::get('priority/{priority}.xml', [EventFeedController::class, 'byPriority'])->name('priority');

        // Events by country (ISO code)
        Route::get('countries/{code}.xml', [EventFeedController::class, 'byCountry'])->name('countries');

        // Events by type
        Route::get('types/{type}.xml', [EventFeedController::class, 'byEventType'])->name('types');

        // Events by region
        Route::get('regions/{region}.xml', [EventFeedController::class, 'byRegion'])->name('regions');
    });

    // Country-Feeds: /feed/countries/...
    Route::prefix('countries')->name('countries.')->group(function () {
        // All countries with details
        Route::get('names/all.xml', [CountryFeedController::class, 'allCountries'])->name('all');

        // Countries by continent (EU, AS, AF, NA, SA, OC, AN)
        Route::get('continent/{code}.xml', [CountryFeedController::class, 'byContinent'])->name('continent');

        // EU member countries
        Route::get('eu.xml', [CountryFeedController::class, 'euCountries'])->name('eu');

        // Schengen member countries
        Route::get('schengen.xml', [CountryFeedController::class, 'schengenCountries'])->name('schengen');
    });

    // Placeholder für zukünftige Feed-Kategorien:
    // Route::prefix('news')->name('news.')->group(function () { ... });
    // Route::prefix('advisories')->name('advisories.')->group(function () { ... });
    // Route::prefix('topics')->name('topics.')->group(function () { ... });
});

Route::get('/booking', function () {
    if (!config('app.dashboard_booking_enabled', true)) {
        abort(404);
    }

    $customer = auth('customer')->user();

    return view('livewire.pages.booking', [
        'customer' => $customer,
    ]);
})->name('booking');

Route::get('/branches', function () {
    // Nur für eingeloggte Kunden mit aktiviertem Branch Management
    if (!auth('customer')->check() || !auth('customer')->user()->branch_management_active) {
        abort(404);
    }

    $customer = auth('customer')->user();

    return view('livewire.pages.branches', [
        'customer' => $customer,
    ]);
})->name('branches');

Route::get('/cruise', function () {
    return view('livewire.pages.cruise');
})->name('cruise');

// Meine Reisenden - nur für eingeloggte Kunden mit gültigem Token
Route::get('/my-travelers', [\App\Http\Controllers\Customer\MyTravelersController::class, 'index'])
    ->middleware('auth:customer')
    ->name('my-travelers');

Route::get('/my-travelers/active', [\App\Http\Controllers\Customer\MyTravelersController::class, 'getActiveTravelers'])
    ->middleware('auth:customer')
    ->name('my-travelers.active');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', [SettingsController::class, 'profile'])->name('settings.profile');
    Route::get('settings/password', [SettingsController::class, 'password'])->name('settings.password');
    Route::get('settings/appearance', [SettingsController::class, 'appearance'])->name('settings.appearance');
});

// Admin SSO Logs Routes
Route::prefix('admin')->name('admin.')->middleware(['auth:web'])->group(function () {
    Route::prefix('sso-logs')->name('sso-logs.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\SsoLogController::class, 'index'])->name('index');
        Route::get('/stats', [App\Http\Controllers\Admin\SsoLogController::class, 'stats'])->name('stats');
        Route::get('/{requestId}', [App\Http\Controllers\Admin\SsoLogController::class, 'show'])->name('show');
    });
});

require __DIR__.'/auth.php';
require __DIR__.'/customer-auth.php';

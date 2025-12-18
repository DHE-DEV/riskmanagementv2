<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GdacsController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\EventFeedController;
use App\Http\Controllers\CountryFeedController;
use App\Models\EventClick;

Route::get('/', function () {
    $eventId = request()->query('event');
    $viewParam = request()->query('view');
    $sharedEvent = null;

    // Detect mobile/tablet devices
    $agent = new \Jenssegers\Agent\Agent();
    $isMobile = $agent->isMobile() || $agent->isTablet();

    // Load shared event if event ID is provided
    if ($eventId) {
        $sharedEvent = \App\Models\CustomEvent::with(['countries.capital', 'eventType', 'eventTypes'])
            ->where('is_active', true)
            ->find($eventId);

        // Track direct link access
        if ($sharedEvent) {
            try {
                EventClick::create([
                    'custom_event_id' => $sharedEvent->id,
                    'click_type' => 'direct_link',
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'session_id' => session()->getId() ?? null,
                    'user_id' => auth()->id(),
                    'clicked_at' => now(),
                ]);
            } catch (\Exception $e) {
                // Silently fail - don't break the page for tracking errors
                \Log::warning('Direct link tracking failed: ' . $e->getMessage());
            }
        }

        // Show mobile-optimized event view for mobile devices with event parameter
        if ($sharedEvent && $isMobile) {
            return view('livewire.pages.event-mobile', [
                'event' => $sharedEvent,
            ]);
        }
    }

    // Mobile routing (without event parameter)
    if ($isMobile && !$eventId) {
        // Map view for mobile
        if ($viewParam === 'map') {
            return view('livewire.pages.dashboard-mobile-map');
        }

        // Default mobile view (feed)
        return view('livewire.pages.dashboard-mobile');
    }

    // Desktop view
    return view('livewire.pages.dashboard', [
        'sharedEvent' => $sharedEvent,
    ]);
})->name('home');

/*
|--------------------------------------------------------------------------
| Embed Routes (for iframe embedding on external websites)
|--------------------------------------------------------------------------
|
| These routes provide embeddable versions of the dashboard, events list,
| and map without header, footer, or navigation. They can be embedded
| via iframe on customer websites.
|
| Usage:
|   <iframe src="https://global-travel-monitor.eu/embed/events" width="100%" height="600"></iframe>
|
| Optional parameters:
|   ?filter=critical|high|medium  - Pre-filter by priority
|   ?lang=de|en                   - Language (default: de)
|   ?hide_badge=1                 - Hide "Powered by" badge
|
*/
Route::prefix('embed')->name('embed.')->middleware(['allow.embedding'])->group(function () {
    // Events list (embeddable)
    Route::get('/events', function () {
        return view('livewire.pages.embed.events');
    })->name('events');

    // Map view (embeddable)
    Route::get('/map', function () {
        return view('livewire.pages.embed.map');
    })->name('map');

    // Dashboard with sidebar and map (embeddable)
    Route::get('/dashboard', function () {
        return view('livewire.pages.embed.dashboard');
    })->name('dashboard');

    // Alias: /embed redirects to /embed/dashboard
    Route::get('/', function () {
        return redirect()->route('embed.dashboard');
    });
});

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

// Plugin/Embed Dokumentation
Route::get('/doc-plugin', function () {
    return view('livewire.pages.doc-plugin');
})->name('doc-plugin');

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

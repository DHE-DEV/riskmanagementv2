<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GdacsController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\FeedController;

Route::get('/', function () {
    return view('livewire.pages.dashboard');
})->name('home');

Route::get('/entry-conditions', function () {
    if (!config('app.entry_conditions_enabled', true)) {
        abort(404);
    }
    return view('livewire.pages.entry-conditions');
})->name('entry-conditions');

// RSS/Atom Feed Routes
Route::prefix('feed')->name('feed.')->group(function () {
    // All events feeds
    Route::get('events.xml', [FeedController::class, 'allEvents'])->name('events.rss');
    Route::get('events.atom', [FeedController::class, 'allEventsAtom'])->name('events.atom');

    // Critical/high priority events
    Route::get('critical.xml', [FeedController::class, 'criticalEvents'])->name('critical');

    // Events by country (ISO code)
    Route::get('countries/{code}.xml', [FeedController::class, 'byCountry'])->name('countries');

    // Events by type
    Route::get('types/{type}.xml', [FeedController::class, 'byEventType'])->name('types');

    // Events by region
    Route::get('regions/{region}.xml', [FeedController::class, 'byRegion'])->name('regions');
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

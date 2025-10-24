<?php

use App\Http\Controllers\Auth\Customer\LoginController;
use App\Http\Controllers\Auth\Customer\RegisterController;
use App\Http\Controllers\Auth\Customer\SocialAuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Customer Authentication Routes
|--------------------------------------------------------------------------
|
| These routes handle customer authentication including standard login,
| registration, and social authentication via OAuth providers.
|
*/

// Guest routes (not authenticated)
Route::middleware('guest:customer')->prefix('customer')->name('customer.')->group(function () {

    // Standard Login
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store']);

    // Standard Registration
    Route::get('register', [RegisterController::class, 'create'])->name('register');
    Route::post('register', [RegisterController::class, 'store']);

    // Social Authentication
    Route::prefix('auth')->name('auth.')->group(function () {
        // Redirect to provider
        Route::get('{provider}/redirect', [SocialAuthController::class, 'redirect'])
            ->name('redirect')
            ->where('provider', 'facebook|google|linkedin|twitter');

        // Handle provider callback
        Route::get('{provider}/callback', [SocialAuthController::class, 'callback'])
            ->name('callback')
            ->where('provider', 'facebook|google|linkedin|twitter');
    });
});

// Authenticated customer routes
Route::middleware('auth:customer')->prefix('customer')->name('customer.')->group(function () {

    // Logout
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

    // Dashboard (handled by routes/customer.php to avoid duplication)
    // Route::get('dashboard', ...)->name('dashboard');
});

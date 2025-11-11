<?php

use Illuminate\Support\Facades\Route;
use App\Modules\PdsAuthInt\Http\Controllers\SPController;

/*
|--------------------------------------------------------------------------
| PdsAuthInt Web Routes
|--------------------------------------------------------------------------
|
| Web-Routen für das PdsAuthInt-Modul
| Diese Routen werden für die SSO-Integration verwendet
|
| Web routes for the PdsAuthInt module
| These routes are used for SSO integration
|
*/

Route::prefix('sso')->group(function () {
    /*
     * SSO Login Endpoint
     *
     * Empfängt ein One-Time Token (OTT) als Query-Parameter
     * Führt JIT (Just-In-Time) Provisioning durch
     * Loggt den Kunden ein und leitet zum Dashboard weiter
     *
     * GET /sso/login?ott=abc123...
     *
     * Receives a One-Time Token (OTT) as query parameter
     * Performs JIT (Just-In-Time) Provisioning
     * Logs in the customer and redirects to dashboard
     *
     * GET /sso/login?ott=abc123...
     */
    Route::get('/login', [SPController::class, 'handleLogin'])
        ->name('sso.login');
});

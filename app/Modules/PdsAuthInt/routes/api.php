<?php

use Illuminate\Support\Facades\Route;
use App\Modules\PdsAuthInt\Http\Controllers\SPController;

/*
|--------------------------------------------------------------------------
| PdsAuthInt API Routes
|--------------------------------------------------------------------------
|
| API-Routen für das PdsAuthInt-Modul
| Diese Routen werden für die SSO-Integration verwendet
|
| API routes for the PdsAuthInt module
| These routes are used for SSO integration
|
*/

Route::prefix('api/sso')->group(function () {
    /*
     * JWT Exchange Endpoint
     *
     * Empfängt ein JWT vom IdP und tauscht es gegen ein One-Time Token (OTT)
     * POST /api/sso/exchange
     * Body: { "jwt": "eyJ..." }
     * Response: { "ott": "abc123...", "redirect_url": "..." }
     *
     * Receives a JWT from the IdP and exchanges it for a One-Time Token (OTT)
     * POST /api/sso/exchange
     * Body: { "jwt": "eyJ..." }
     * Response: { "ott": "abc123...", "redirect_url": "..." }
     */
    Route::post('/exchange', [SPController::class, 'exchangeToken'])
        ->name('sso.exchange');
});

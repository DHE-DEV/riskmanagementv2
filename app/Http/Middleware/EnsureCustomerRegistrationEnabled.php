<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sperrt die Self-Registrierung, wenn sie deaktiviert ist
 * (config('app.customer_registration_enabled') = false).
 *
 * Kunden entstehen dann ausschliesslich ueber SSO (JIT-Provisioning);
 * Aufrufe der Register-Routen werden auf den Login geleitet.
 */
class EnsureCustomerRegistrationEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('app.customer_registration_enabled')) {
            return redirect()->route('customer.login');
        }

        return $next($request);
    }
}

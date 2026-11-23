<?php

use App\Models\Customer;
use Illuminate\Support\Facades\Auth;

/**
 * "Angemeldet bleiben"-Cookie eines Kunden: so, wie Laravel es beim Anmelden bildet.
 */
function customerRememberCookie(Customer $customer, ?string $passwordForHash): array
{
    $guard = Auth::guard('customer');

    return [$guard->getRecallerName(), $customer->id.'|'.$customer->remember_token.'|'.$guard->hashPasswordForCookie($passwordForHash ?? '')];
}

it('meldet einen SSO-Kunden ohne Passwort ueber sein Angemeldet-bleiben-Cookie wieder an', function () {
    $customer = Customer::factory()->create(['password' => null, 'remember_token' => 'erinnerung-sso']);
    [$name, $value] = customerRememberCookie($customer, null);

    // Vorher: TypeError in hash_equals() und eine 500-Seite.
    $this->withCookie($name, $value)
        ->get('/global-travel-monitor?v=1&user-state=logged-in')
        ->assertOk();

    $this->assertAuthenticatedAs($customer, 'customer');
});

it('leitet einen SSO-Kunden ohne Passwort bei unpassendem Cookie zur stillen SSO-Anmeldung statt mit Fehler 500', function () {
    $customer = Customer::factory()->create(['password' => null, 'remember_token' => 'erinnerung-alt']);
    [$name, $value] = customerRememberCookie($customer, 'cookie-aus-altem-format');

    // Vorher: TypeError in hash_equals(), weil das Passwort null ist.
    $this->withCookie($name, $value)
        ->get('/global-travel-monitor?v=1&user-state=logged-in')
        ->assertRedirectContains('silent=1');

    $this->assertGuest('customer');
});

it('laesst ein Angemeldet-bleiben-Cookie mit falschem Passwort-Hash nicht durch', function () {
    $customer = Customer::factory()->create(['remember_token' => 'erinnerung-pw']);
    [$name, $value] = customerRememberCookie($customer, 'anderer-hash');

    $this->withCookie($name, $value)->get('/global-travel-monitor');

    $this->assertGuest('customer');
});

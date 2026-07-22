<?php

use App\Models\Customer;
use App\Services\PassolutionApiService;
use Laravel\Socialite\Facades\Socialite;
use SocialiteProviders\Manager\OAuth2\User as SocialiteUser;

/**
 * Stellt einen gefakten Socialite-User bereit (wie ihn Keycloak/Laravel-Passport
 * nach erfolgreichem OAuth-Flow zurueckgeben wuerden).
 */
function fakeSsoUser(string $id, string $email): SocialiteUser
{
    $user = new SocialiteUser;
    $user->setRaw(['id' => $id, 'email' => $email])->map([
        'id' => $id,
        'email' => $email,
        'name' => 'Test User',
        'avatar' => null,
    ]);
    $user->token = 'access-token';
    $user->refreshToken = 'refresh-token';
    $user->setAccessTokenResponseBody(['id_token' => 'id-token-xyz']);

    return $user;
}

/**
 * Bindet einen gestubbten PassolutionApiService, der die angegebenen Stammdaten
 * liefert (oder null). Verhindert externe HTTP-Requests im Test.
 *
 * @param  array<string, mixed>|null  $account
 */
function fakePassolutionApi(?array $account = null): void
{
    $service = Mockery::mock(PassolutionApiService::class);
    $service->shouldReceive('fetchAccountByEmail')->andReturn($account);
    $service->shouldReceive('fetchSubscriptionWithToken')->andReturn(null);
    $service->shouldReceive('fetchSubscriptionById')->andReturn(null);
    app()->instance(PassolutionApiService::class, $service);
}

beforeEach(function () {
    // JIT-Provisioning und der Stammdaten-Abgleich rufen die PDS-API auf.
    fakePassolutionApi();
});

test('laravelpassport-Treiber legt neuen Kunden mit provider=laravelpassport an', function () {
    config(['services.sso.driver' => 'laravelpassport']);

    $provider = Mockery::mock();
    $provider->shouldReceive('user')->once()->andReturn(fakeSsoUser('lp-123', 'passport@example.com'));
    Socialite::shouldReceive('driver')->with('laravelpassport')->andReturn($provider);

    $this->get('/auth/callback')->assertRedirect('/customer/dashboard');

    $this->assertAuthenticated('customer');

    $customer = Customer::where('email', 'passport@example.com')->first();
    expect($customer)->not->toBeNull();
    expect($customer->provider)->toBe('laravelpassport');
    expect($customer->provider_id)->toBe('lp-123');
    expect(session('keycloak_email'))->toBe('passport@example.com');
});

test('keycloak-Treiber nutzt PKCE und speichert provider=keycloak', function () {
    config(['services.sso.driver' => 'keycloak']);

    $provider = Mockery::mock();
    // Keycloak-Flow ruft zusaetzlich enablePKCE() in der Kette auf.
    $provider->shouldReceive('enablePKCE')->andReturnSelf();
    $provider->shouldReceive('user')->once()->andReturn(fakeSsoUser('kc-9', 'keycloak@example.com'));
    Socialite::shouldReceive('driver')->with('keycloak')->andReturn($provider);

    $this->get('/auth/callback')->assertRedirect('/customer/dashboard');

    $customer = Customer::where('email', 'keycloak@example.com')->first();
    expect($customer->provider)->toBe('keycloak');
});

test('Login-Seite leitet bei force_redirect direkt zum SSO-Provider weiter', function () {
    config(['services.sso.force_redirect' => true]);

    $this->get(route('customer.login'))
        ->assertRedirect(route('auth.keycloak.redirect'));
});

test('Login-Seite wird bei SSO-Fehler-Flash gezeigt (kein Redirect-Loop)', function () {
    config(['services.sso.force_redirect' => true]);

    $this->withSession(['error' => 'Anmeldung fehlgeschlagen'])
        ->get(route('customer.login'))
        ->assertOk()
        ->assertViewIs('auth.customer.login');
});

test('Login-Seite wird bei force_redirect=false normal angezeigt', function () {
    config(['services.sso.force_redirect' => false]);

    $this->get(route('customer.login'))
        ->assertOk()
        ->assertViewIs('auth.customer.login');
});

test('laravelpassport-Login haengt prompt=login an, wenn aktiviert', function () {
    config([
        'services.sso.driver' => 'laravelpassport',
        'services.sso.prompt_login' => true,
        'services.laravelpassport.host' => 'https://auth.example.test',
    ]);

    $response = $this->get(route('auth.keycloak.redirect'));

    $response->assertRedirectContains('auth.example.test/oauth/authorize');
    $response->assertRedirectContains('prompt=login');
});

test('laravelpassport-Login ohne prompt=login, wenn deaktiviert', function () {
    config([
        'services.sso.driver' => 'laravelpassport',
        'services.sso.prompt_login' => false,
        'services.laravelpassport.host' => 'https://auth.example.test',
    ]);

    $response = $this->get(route('auth.keycloak.redirect'));

    $response->assertRedirectContains('auth.example.test/oauth/authorize');
    expect($response->headers->get('Location'))->not->toContain('prompt=login');
});

test('Logout eines Passport-Kunden leitet zu SSO_LOGOUT_URL', function () {
    config(['services.sso.logout_url' => 'https://auth.example.test/logout']);

    $customer = Customer::factory()->create([
        'provider' => 'laravelpassport',
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($customer, 'customer')
        ->post(route('customer.logout'));

    $response->assertRedirectContains('auth.example.test/logout');
    $response->assertRedirectContains('return_to=');
    $this->assertGuest('customer');
});

test('redirect mit from=iframe merkt sich den iframe-Login in der Session', function () {
    config([
        'services.sso.driver' => 'laravelpassport',
        'services.laravelpassport.host' => 'https://auth.example.test',
    ]);

    $this->get(route('auth.keycloak.redirect', ['from' => 'iframe']))
        ->assertRedirectContains('auth.example.test/oauth/authorize');

    expect(session('sso_iframe_login'))->toBeTrue();
});

test('iframe-Login leitet nach erfolgreichem Callback auf die Done-Seite', function () {
    config(['services.sso.driver' => 'laravelpassport']);

    $provider = Mockery::mock();
    $provider->shouldReceive('user')->once()->andReturn(fakeSsoUser('lp-iframe', 'iframe@example.com'));
    Socialite::shouldReceive('driver')->with('laravelpassport')->andReturn($provider);

    $this->withSession(['sso_iframe_login' => true])
        ->get('/auth/callback')
        ->assertRedirect(route('auth.keycloak.iframe-done'));

    $this->assertAuthenticated('customer');
    // Flag wurde verbraucht (pull), damit Folge-Logins normal aufs Dashboard gehen.
    expect(session('sso_iframe_login'))->toBeNull();
});

test('normaler (Nicht-iframe) Login leitet weiterhin aufs Dashboard', function () {
    config(['services.sso.driver' => 'laravelpassport']);

    $provider = Mockery::mock();
    $provider->shouldReceive('user')->once()->andReturn(fakeSsoUser('lp-top', 'top@example.com'));
    Socialite::shouldReceive('driver')->with('laravelpassport')->andReturn($provider);

    $this->get('/auth/callback')->assertRedirect('/customer/dashboard');
});

test('JIT-Provisioning splittet address_line_1 in Strasse und Hausnummer', function () {
    config(['services.sso.driver' => 'laravelpassport']);

    fakePassolutionApi([
        'name' => 'Reisebuero Muster GmbH',
        'first_name' => 'Max',
        'last_name' => 'Mustermann',
        'address_line_1' => 'Musterweg 12a',
        'zip_code' => '80331',
        'city' => 'Muenchen',
        'country_code' => 'DE',
    ]);

    $provider = Mockery::mock();
    $provider->shouldReceive('user')->once()->andReturn(fakeSsoUser('lp-neu', 'neu@example.com'));
    Socialite::shouldReceive('driver')->with('laravelpassport')->andReturn($provider);

    $this->get('/auth/callback')->assertRedirect('/customer/dashboard');

    $customer = Customer::where('email', 'neu@example.com')->first();
    expect($customer->company_name)->toBe('Reisebuero Muster GmbH');
    expect($customer->company_additional)->toBe('Max Mustermann');
    expect($customer->company_street)->toBe('Musterweg');
    expect($customer->company_house_number)->toBe('12a');
    expect($customer->company_postal_code)->toBe('80331');
    expect($customer->company_city)->toBe('Muenchen');
    expect($customer->company_country)->toBe('DE');
});

test('Vor- und Nachname der Kontaktperson landen im Zusatz (company_additional)', function () {
    $account = ['first_name' => 'Erika', 'last_name' => 'Beispiel'];
    expect(Customer::resolveContactName($account))->toBe('Erika Beispiel');
    expect(Customer::resolveContactName(['first_name' => 'Nur-Vorname']))->toBe('Nur-Vorname');
    expect(Customer::resolveContactName(['last_name' => 'Nur-Nachname']))->toBe('Nur-Nachname');
    expect(Customer::resolveContactName([]))->toBeNull();
    expect(Customer::resolveContactName(['first_name' => '  ', 'last_name' => '']))->toBeNull();
});

test('fehlender Name ueberschreibt bestehenden Zusatz nicht', function () {
    $customer = Customer::factory()->create([
        'email' => 'zusatz@example.com',
        'company_additional' => 'Bestehender Zusatz',
    ]);

    fakePassolutionApi([
        'name' => 'Firma ohne Kontaktperson',
        'address_line_1' => 'Weg 3',
        'zip_code' => '10115',
        'city' => 'Berlin',
        'country_code' => 'DE',
    ]);

    $customer->syncPdsAccountData(0);

    $customer->refresh();
    expect($customer->company_additional)->toBe('Bestehender Zusatz');
});

test('Firmenname wird aus dem company-Feld gezogen, nicht aus dem ueberladenen name-Feld', function () {
    config(['services.sso.driver' => 'laravelpassport']);

    // In der PDS-API ist `name` oft die Login-E-Mail o. ae.; der Firmenname
    // steht im dedizierten company-/company_name-Feld.
    fakePassolutionApi([
        'name' => 'sso-login@example.com',
        'company' => 'Reisebuero Sonnenschein GmbH',
        'address_line_1' => 'Sonnenallee 5',
        'zip_code' => '12045',
        'city' => 'Berlin',
        'country_code' => 'DE',
    ]);

    $provider = Mockery::mock();
    $provider->shouldReceive('user')->once()->andReturn(fakeSsoUser('lp-firma', 'sso-login@example.com'));
    Socialite::shouldReceive('driver')->with('laravelpassport')->andReturn($provider);

    $this->get('/auth/callback')->assertRedirect('/customer/dashboard');

    $customer = Customer::where('email', 'sso-login@example.com')->first();
    expect($customer->company_name)->toBe('Reisebuero Sonnenschein GmbH');
});

test('company_name hat Vorrang vor company und name', function () {
    $account = [
        'company_name' => 'Primaer GmbH',
        'company' => 'Sekundaer GmbH',
        'name' => 'tertiaer@example.com',
    ];

    expect(Customer::resolveCompanyName($account))->toBe('Primaer GmbH');
    expect(Customer::resolveCompanyName(['company' => 'Nur company GmbH']))->toBe('Nur company GmbH');
    expect(Customer::resolveCompanyName(['name' => 'Nur name GmbH']))->toBe('Nur name GmbH');
    expect(Customer::resolveCompanyName(['name' => '   ']))->toBeNull();
    expect(Customer::resolveCompanyName([]))->toBeNull();
});

test('geaenderte Adresse wird beim naechsten Login in den Kundendatensatz uebernommen', function () {
    config(['services.sso.driver' => 'laravelpassport']);

    $customer = Customer::factory()->create([
        'email' => 'umzug@example.com',
        'provider' => 'laravelpassport',
        'provider_id' => 'lp-umzug',
        'company_name' => 'Reisebuero Alt',
        'company_street' => 'Altstrasse',
        'company_house_number' => '1',
        'company_postal_code' => '10115',
        'company_city' => 'Berlin',
        'company_country' => 'DE',
        'pds_last_synced_at' => now(),
    ]);

    // Auf der Plattform ist die Adresse inzwischen eine andere.
    fakePassolutionApi([
        'name' => 'Reisebuero Neu',
        'address_line_1' => 'Neuegasse 7',
        'zip_code' => '20095',
        'city' => 'Hamburg',
        'country_code' => 'DE',
    ]);

    $provider = Mockery::mock();
    $provider->shouldReceive('user')->once()->andReturn(fakeSsoUser('lp-umzug', 'umzug@example.com'));
    Socialite::shouldReceive('driver')->with('laravelpassport')->andReturn($provider);

    $this->get('/auth/callback')->assertRedirect('/customer/dashboard');

    $customer->refresh();
    expect($customer->company_name)->toBe('Reisebuero Neu');
    expect($customer->company_street)->toBe('Neuegasse');
    expect($customer->company_house_number)->toBe('7');
    expect($customer->company_postal_code)->toBe('20095');
    expect($customer->company_city)->toBe('Hamburg');
});

test('Adresszeile ohne Hausnummer leert die alte Hausnummer', function () {
    $customer = Customer::factory()->create([
        'email' => 'ohne-nr@example.com',
        'company_street' => 'Altstrasse',
        'company_house_number' => '1',
    ]);

    fakePassolutionApi([
        'name' => 'Reisebuero Ohne Nummer',
        'address_line_1' => 'Am Hauptbahnhof',
        'zip_code' => '50667',
        'city' => 'Koeln',
        'country_code' => 'DE',
    ]);

    $customer->syncPdsAccountData(0);

    $customer->refresh();
    expect($customer->company_street)->toBe('Am Hauptbahnhof');
    expect($customer->company_house_number)->toBeNull();
});

test('Callback ohne E-Mail vom Anbieter wird abgelehnt', function () {
    config(['services.sso.driver' => 'laravelpassport']);

    $provider = Mockery::mock();
    $provider->shouldReceive('user')->once()->andReturn(fakeSsoUser('lp-noemail', ''));
    Socialite::shouldReceive('driver')->with('laravelpassport')->andReturn($provider);

    $this->get('/auth/callback')->assertRedirect(route('customer.login'));

    $this->assertGuest('customer');
});

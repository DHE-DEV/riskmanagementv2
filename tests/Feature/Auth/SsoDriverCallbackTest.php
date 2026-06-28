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

beforeEach(function () {
    // JIT-Provisioning ruft den PDS-Stammdaten-Lookup auf – im Test stubben,
    // damit kein externer HTTP-Request rausgeht.
    $service = Mockery::mock(PassolutionApiService::class);
    $service->shouldReceive('fetchAccountByEmail')->andReturn(null);
    app()->instance(PassolutionApiService::class, $service);
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

test('Callback ohne E-Mail vom Anbieter wird abgelehnt', function () {
    config(['services.sso.driver' => 'laravelpassport']);

    $provider = Mockery::mock();
    $provider->shouldReceive('user')->once()->andReturn(fakeSsoUser('lp-noemail', ''));
    Socialite::shouldReceive('driver')->with('laravelpassport')->andReturn($provider);

    $this->get('/auth/callback')->assertRedirect(route('customer.login'));

    $this->assertGuest('customer');
});

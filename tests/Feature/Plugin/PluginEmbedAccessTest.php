<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Plugin-Embed: Rueckwaertskompatibilitaet der Kunden-iframes
|--------------------------------------------------------------------------
|
| Kunden haben die Embed-URLs fest in ihre Webseiten eingebaut, z. B.
|
|   <iframe src="https://global-travel-monitor.eu/embed/events?key=pk_live_..."></iframe>
|
| Diese Einbindungen duerfen sich nicht aendern - auch dann nicht, wenn die
| Plattform kuenftig unter platform.passolution.de ausgerollt wird und beide
| Hostnames auf dieselbe Installation zeigen.
|
| Die Tests halten fest, was dafuer gelten muss:
|   1. Die drei Embed-Routen bleiben unter jedem Hostnamen erreichbar.
|   2. Die Antwort bleibt iframe-faehig (kein X-Frame-Options).
|   3. Die Zugriffskontrolle aus ValidateEmbedKey bleibt unveraendert.
|   4. Kein Auth-/SSO-Redirect darf das iframe kapern.
|
*/

use App\Models\Customer;
use App\Models\PluginClient;
use App\Models\PluginUsageEvent;

/**
 * Legt einen aktiven Plugin-Client mit einer registrierten Domain an.
 */
function embedClient(array $attributes = []): PluginClient
{
    $customer = Customer::factory()->create();

    $client = PluginClient::create(array_merge([
        'customer_id' => $customer->id,
        'company_name' => 'Embed Test GmbH',
        'contact_name' => 'Embed Tester',
        'email' => $customer->email,
        'status' => 'active',
    ], $attributes));

    $client->generateKey();
    $client->addDomain('kundenseite.de');

    return $client->fresh();
}

function embedKey(PluginClient $client): string
{
    return $client->keys()->where('is_active', true)->firstOrFail()->public_key;
}

/** Die drei Routen, die Kunden laut Doku eingebunden haben. */
dataset('embed routes', [
    'events' => ['/embed/events'],
    'map' => ['/embed/map'],
    'dashboard' => ['/embed/dashboard'],
]);

/** Beide Hostnames zeigen auf dieselbe Installation. */
dataset('platform hosts', [
    'legacy host' => ['global-travel-monitor.eu'],
    'new host' => ['platform.passolution.de'],
]);

test('embed route is reachable with a valid key from a registered domain', function (string $route) {
    $client = embedClient();

    $this->withHeader('Referer', 'https://kundenseite.de/reiseinfos')
        ->get($route.'?key='.embedKey($client))
        ->assertSuccessful();
})->with('embed routes');

test('embed route stays reachable under every hostname', function (string $host) {
    $client = embedClient();
    $key = embedKey($client);

    // Absolute URL statt Host-Header: nur so uebernimmt der Testclient den
    // Hostnamen tatsaechlich in den Request.
    foreach (['/embed/events', '/embed/map', '/embed/dashboard'] as $route) {
        $this->withHeader('Referer', 'https://kundenseite.de/')
            ->get('https://'.$host.$route.'?key='.$key)
            ->assertSuccessful();
    }
})->with('platform hosts');

test('embed response is embeddable in a third party iframe', function (string $route) {
    $client = embedClient();

    $response = $this->withHeader('Referer', 'https://kundenseite.de/')
        ->get($route.'?key='.embedKey($client));

    // X-Frame-Options wuerde die Einbettung in jedem Browser verhindern.
    $response->assertHeaderMissing('X-Frame-Options');
    expect($response->headers->get('Content-Security-Policy'))->toContain('frame-ancestors');
})->with('embed routes');

test('embed accepts the www variant of a registered domain', function () {
    $client = embedClient();

    $this->withHeader('Referer', 'https://www.kundenseite.de/')
        ->get('/embed/events?key='.embedKey($client))
        ->assertSuccessful();
});

test('embed rejects an unregistered domain', function () {
    $client = embedClient();

    $this->withHeader('Referer', 'https://fremde-domain.de/')
        ->get('/embed/events?key='.embedKey($client))
        ->assertForbidden();
});

test('embed rejects a deactivated domain', function () {
    $client = embedClient();
    $client->domains()->update(['is_active' => false]);

    $this->withHeader('Referer', 'https://kundenseite.de/')
        ->get('/embed/events?key='.embedKey($client))
        ->assertForbidden();
});

test('embed rejects an unknown or malformed key', function (string $key) {
    embedClient();

    $this->withHeader('Referer', 'https://kundenseite.de/')
        ->get('/embed/events?key='.$key)
        ->assertForbidden();
})->with([
    'unknown key' => ['pk_live_thiskeydoesnotexist0000000000'],
    'wrong prefix' => ['sk_test_abcdefghijklmnopqrstuvwxyz0123'],
]);

test('embed rejects a missing key', function () {
    embedClient();

    $this->withHeader('Referer', 'https://kundenseite.de/')
        ->get('/embed/events')
        ->assertForbidden();
});

test('embed rejects a deactivated key', function () {
    $client = embedClient();
    $key = embedKey($client);
    $client->keys()->update(['is_active' => false]);

    $this->withHeader('Referer', 'https://kundenseite.de/')
        ->get('/embed/events?key='.$key)
        ->assertForbidden();
});

test('embed rejects an inactive client', function () {
    $client = embedClient();
    $key = embedKey($client);
    $client->update(['status' => 'suspended']);

    $this->withHeader('Referer', 'https://kundenseite.de/')
        ->get('/embed/events?key='.$key)
        ->assertForbidden();
});

test('embed without referer requires app access', function () {
    $client = embedClient(['allow_app_access' => false]);

    $this->get('/embed/events?key='.embedKey($client))
        ->assertForbidden();

    $client->update(['allow_app_access' => true]);

    $this->get('/embed/events?key='.embedKey($client))
        ->assertSuccessful();
});

test('embed records a usage event per view', function () {
    $client = embedClient();

    $this->withHeader('Referer', 'https://kundenseite.de/reiseinfos')
        ->get('/embed/events?key='.embedKey($client))
        ->assertSuccessful();

    $event = PluginUsageEvent::where('plugin_client_id', $client->id)->latest('id')->first();

    expect($event)->not->toBeNull()
        ->and($event->event_type)->toBe('embed_view')
        ->and($event->domain)->toBe('kundenseite.de')
        ->and($event->path)->toBe('embed/events');
});

test('embed alias redirects to the dashboard on the same host', function () {
    $client = embedClient();

    $response = $this->withHeader('Referer', 'https://kundenseite.de/')
        ->get('https://global-travel-monitor.eu/embed?key='.embedKey($client));

    $response->assertRedirect();

    // Der Redirect darf den Kunden weder auf einen anderen Hostnamen werfen
    // noch das Schema auf http herabstufen (Mixed-Content im iframe).
    $location = $response->headers->get('Location');
    expect(parse_url($location, PHP_URL_HOST))->toBe('global-travel-monitor.eu')
        ->and(parse_url($location, PHP_URL_PATH))->toBe('/embed/dashboard')
        ->and(parse_url($location, PHP_URL_SCHEME))->toBe('https');
});

test('embed ignores the platform user-state parameter', function (string $route) {
    $client = embedClient();
    $key = embedKey($client);

    // SyncPlatformUserState wuerde bei user-state=logged-in sonst einen
    // SSO-Redirect ausloesen. Keycloak verweigert die iframe-Einbettung,
    // der Kunde saehe ein leeres iframe.
    foreach (['logged-in', 'logged-out'] as $state) {
        $this->withHeader('Referer', 'https://kundenseite.de/')
            ->get($route.'?key='.$key.'&user-state='.$state)
            ->assertSuccessful();
    }
})->with('embed routes');

test('embed data endpoints stay public and reachable under every hostname', function (string $host) {
    foreach ([
        '/api/custom-events/dashboard-events?limit=5',
        '/api/custom-events/map-events',
        '/api/custom-events/event-types',
    ] as $endpoint) {
        $this->getJson('https://'.$host.$endpoint)->assertSuccessful();
    }
})->with('platform hosts');

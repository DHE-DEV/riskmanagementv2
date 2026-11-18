<?php

use App\Models\Customer;
use Illuminate\Support\Facades\Cache;

/**
 * Rate Limit der GTM-API: Höhe je Kunde, gezählt je Token und IP – damit ein geteilter
 * App-Token oder eine Integration nicht das Kontingent anderer Tokens desselben Kunden aufbraucht.
 */
function rateLimitedCustomer(int $limit): Customer
{
    return Customer::factory()->create(['gtm_api_enabled' => true, 'gtm_api_rate_limit' => $limit]);
}

/**
 * Eine Anfrage wie aus einem eigenen Prozess: ohne im Test zwischengespeicherten Login.
 */
function gtmRequest($test, string $token, string $ip)
{
    app('auth')->forgetGuards();

    return $test->withToken($token)->withServerVariables(['REMOTE_ADDR' => $ip])->getJson('/api/v1/continents');
}

function exhaustLimit($test, string $token, string $ip, int $times): void
{
    for ($i = 0; $i < $times; $i++) {
        gtmRequest($test, $token, $ip)->assertOk();
    }
}

beforeEach(fn () => Cache::flush());

it('lehnt nach Erreichen des Limits weitere Anfragen desselben Tokens von derselben IP ab', function () {
    $token = rateLimitedCustomer(2)->createToken('Integration', ['gtm:read'])->plainTextToken;
    exhaustLimit($this, $token, '10.0.0.1', 2);

    $response = gtmRequest($this, $token, '10.0.0.1');

    $response->assertTooManyRequests()
        ->assertHeader('X-RateLimit-Limit', '2');
});

it('gibt jedem Token desselben Kunden ein eigenes Kontingent', function () {
    $customer = rateLimitedCustomer(2);
    $appToken = $customer->createToken('App', ['gtm:read'])->plainTextToken;
    $docsToken = $customer->createToken('Doku-Test', ['gtm:read'])->plainTextToken;
    exhaustLimit($this, $appToken, '10.0.0.1', 2);

    $response = gtmRequest($this, $docsToken, '10.0.0.1');

    $response->assertOk();
});

it('zählt einen geteilten Token je IP getrennt', function () {
    $token = rateLimitedCustomer(2)->createToken('App', ['gtm:read'])->plainTextToken;
    exhaustLimit($this, $token, '10.0.0.1', 2);

    $response = gtmRequest($this, $token, '10.0.0.2');

    $response->assertOk();
});

<?php

use App\Models\Customer;

/**
 * Die API antwortet auch ohne "Accept: application/json" mit JSON statt mit einer
 * Umleitung auf die Login-Seite; Web-Seiten leiten Gäste weiterhin zum Login.
 */
it('antwortet ohne Token mit 401 JSON statt auf die Login-Seite umzuleiten', function (string $url) {
    $response = $this->get($url);

    $response->assertUnauthorized()
        ->assertExactJson(['message' => 'Unauthenticated.']);
})->with([
    'API-Pfad' => ['/api/v1/events?lang=de'],
    'API-Subdomain' => ['http://api.global-travel-monitor.de/v1/events'],
]);

it('antwortet bei Validierungsfehlern mit 422 JSON', function () {
    $token = Customer::factory()->create(['gtm_api_enabled' => true])->createToken('Test', ['gtm:read'])->plainTextToken;

    $response = $this->withToken($token)->get('/api/v1/events?lang=fr');

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['lang' => 'The selected lang is invalid.']);
});

it('antwortet bei unbekannten API-Pfaden mit 404 JSON', function () {
    $response = $this->get('/api/v1/gibt-es-nicht');

    $response->assertNotFound()
        ->assertHeader('Content-Type', 'application/json');
});

it('leitet Gäste auf Web-Seiten weiterhin zum Login', function () {
    $response = $this->get('/customer/dashboard');

    $response->assertRedirect(route('customer.login'));
});

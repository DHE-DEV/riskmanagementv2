<?php

use function Pest\Laravel\get;

// DB-frei (AppTestCase ohne RefreshDatabase): testet nur Routing/View-Verhalten.
uses(Tests\AppTestCase::class);

it('redirects /customer/register to the login when registration is disabled', function () {
    config(['app.customer_registration_enabled' => false]);

    get(route('customer.register'))->assertRedirect(route('customer.login'));
});

it('hides the register link on public pages when registration is disabled', function () {
    config([
        'app.customer_registration_enabled' => false,
        'services.passolution.iframe_sso_secret' => null,
    ]);

    $response = get(route('travel-requirements-service'));

    $response->assertOk();
    $response->assertDontSee('title="Registrieren"', false);
    $response->assertDontSee(route('customer.register'), false);
});

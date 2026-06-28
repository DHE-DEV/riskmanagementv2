<?php

use function Pest\Laravel\get;
use function Pest\Laravel\withSession;

// Laravel-TestCase (gebootete App) OHNE RefreshDatabase: die TRS-Seite rendert nur
// eine View und fasst keine DB an (Session-Driver im Test = array). So laeuft der
// Test auch ohne erreichbaren MySQL-Container.
uses(Tests\AppTestCase::class);

beforeEach(function () {
    config([
        'services.passolution.web_url' => 'https://embed.example.test',
        'services.passolution.iframe_ui_params' => 'menu-hide=gtm,hc,is&ui-hide=is',
    ]);
});

it('embeds the homepage directly when no iframe sso secret is configured', function () {
    // Standard (Cookie-Loesung): ohne Secret bettet der iframe die Homepage direkt
    // ein und ruft NICHT /auth/sso/logout auf (das wuerde die geteilte Session beenden).
    config(['services.passolution.iframe_sso_secret' => null]);

    $response = get(route('travel-requirements-service'));

    $response->assertOk();
    // & wird im Blade als &amp; escaped – Start der src-URL genuegt als Nachweis.
    $response->assertSee('src="https://embed.example.test?menu-hide=gtm,hc,is', false);
    $response->assertDontSee('/auth/sso/logout', false);
    $response->assertDontSee('/auth/sso/check', false);
});

it('uses the signed sso check handshake when a secret and an email are present', function () {
    // Optionaler Handshake: nur mit gesetztem IFRAME_SSO_SECRET wird der eingeloggte
    // User per signiertem Token an /auth/sso/check durchgereicht.
    config(['services.passolution.iframe_sso_secret' => 'shared-secret']);

    $response = withSession(['keycloak_email' => 'kunde@example.test'])
        ->get(route('travel-requirements-service'));

    $response->assertOk();
    $response->assertSee('/auth/sso/check?', false);
    $response->assertDontSee('/auth/sso/logout', false);
});

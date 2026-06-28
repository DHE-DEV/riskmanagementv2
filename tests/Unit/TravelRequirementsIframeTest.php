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

it('opens the sso login in a modal iframe from the sidebar for guests', function () {
    // Der "Anmelden"-Button in der linken schwarzen Leiste oeffnet das SSO-Login
    // im Modal-iframe (?from=iframe), ohne die Plattform zu verlassen.
    config(['services.passolution.iframe_sso_secret' => null]);

    $response = get(route('travel-requirements-service'));

    $response->assertOk();
    $response->assertSee('onclick="openSsoLoginModal()"', false);
    $response->assertSee('title="Anmelden"', false);
    $response->assertSee('id="sso-login-modal"', false);
    // Modal-iframe laedt den SSO-Login mit from=iframe. (Slash-frei pruefen, da
    // die URL in @json mit escapten Slashes ausgegeben wird: https:\/\/...)
    $response->assertSee('keycloak?from=iframe', false);
});

it('iframe-done page notifies the parent window of a successful login', function () {
    // Nach erfolgreichem iframe-Login meldet diese Mini-Seite dem Parent-Fenster
    // per postMessage den Login -> Parent laedt neu.
    $response = get(route('auth.keycloak.iframe-done'));

    $response->assertOk();
    $response->assertSee('pds-platform-login-success', false);
    $response->assertSee('postMessage', false);
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

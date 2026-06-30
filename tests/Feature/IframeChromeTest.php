<?php

declare(strict_types=1);

test('iframe header is accessible and embeddable', function () {
    $response = $this->get('/iframe/header');

    $response->assertSuccessful()
        ->assertSee('class="header"', false)
        ->assertSee('Passolution Travel Information Platform');

    // allow.embedding-Middleware muss iframe-Einbettung erlauben.
    $response->assertHeaderMissing('X-Frame-Options');
    expect($response->headers->get('Content-Security-Policy'))->toContain('frame-ancestors');
});

test('iframe sidebar is accessible and embeddable', function () {
    $response = $this->get('/iframe/sidebar');

    $response->assertSuccessful()
        ->assertSee('id="main-navigation"', false);

    $response->assertHeaderMissing('X-Frame-Options');
    expect($response->headers->get('Content-Security-Policy'))->toContain('frame-ancestors');
});

test('iframe sidebar highlights the active item', function () {
    $this->get('/iframe/sidebar?active=travel-alert')
        ->assertSuccessful()
        // Aktiver Punkt erhaelt die helle Hervorhebung.
        ->assertSee('bg-white text-black', false);
});

test('iframe footer is accessible and embeddable', function () {
    $response = $this->get('/iframe/footer');

    $response->assertSuccessful()
        ->assertSee('class="footer"', false)
        ->assertSee('Passolution GmbH');

    $response->assertHeaderMissing('X-Frame-Options');
    expect($response->headers->get('Content-Security-Policy'))->toContain('frame-ancestors');
});

test('iframe fragments target the top window for navigation', function () {
    $this->get('/iframe/header')
        ->assertSuccessful()
        ->assertSee('<base target="_top">', false);
});

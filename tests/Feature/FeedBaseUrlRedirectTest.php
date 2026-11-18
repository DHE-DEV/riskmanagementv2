<?php

/**
 * Die Basisadressen der Feeds sind selbst kein Feed und leiten zur Feed-Anleitung weiter.
 */
it('leitet die Feed-Basisadressen zur Feed-Anleitung weiter', function (string $url) {
    $response = $this->get($url);

    $response->assertRedirect('/docs/api/feeds');
})->with([
    'Basis' => ['/feed'],
    'mit Sprache' => ['/feed?lang=de'],
    'Events' => ['/feed/events'],
    'Länder' => ['/feed/countries'],
]);

it('liefert die Feed-Anleitung als Ziel der Weiterleitung', function () {
    $response = $this->get('/docs/api/feeds');

    $response->assertOk()
        ->assertSee('Die Base-URL selbst ist kein Feed');
});

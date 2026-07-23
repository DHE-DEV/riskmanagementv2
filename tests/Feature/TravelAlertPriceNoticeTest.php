<?php

/**
 * Der Preishinweis auf der TravelAlert-Promoseite kommt aus der .env
 * (TRAVEL_ALERT_PRICE_NOTICE). Ist er leer, muss er restlos verschwinden –
 * auch aus den Structured Data, die dabei gueltiges JSON bleiben muessen.
 */
function ldJsonBlocks(string $html): array
{
    preg_match_all(
        '#<script type="application/ld\+json">(.*?)</script>#s',
        $html,
        $matches
    );

    return $matches[1];
}

test('Hinweis aus der Konfiguration erscheint im Bestellmodal', function () {
    config(['app.travel_alert_price_notice' => 'Bis <strong>31.12.2026</strong> kostenlos.']);

    $response = $this->get('/travel-alert');

    $response->assertOk();
    $response->assertSee('Bis <strong>31.12.2026</strong> kostenlos.', false);
});

test('leerer Hinweis entfernt den Absatz ohne Leerzeile', function () {
    config(['app.travel_alert_price_notice' => '']);

    $html = $this->get('/travel-alert')->assertOk()->getContent();

    expect($html)->not->toContain('kostenlos zur Verfügung gestellt');

    // Kein leerer Absatz als Rest des ausgeblendeten Hinweises.
    expect($html)->not->toMatch('#<p class="text-sm text-gray-700 mb-3">\s*</p>#');
    expect($html)->not->toMatch('#<p class="text-sm text-gray-700 mb-2">\s*</p>#');
});

test('die Preisangaben bleiben auch ohne Hinweis stehen', function () {
    config(['app.travel_alert_price_notice' => '']);

    $response = $this->get('/travel-alert');

    $response->assertSee('7,00 EUR', false);
    $response->assertSee('5,00 EUR/Monat', false);
});

test('Structured Data bleiben mit und ohne Hinweis gueltiges JSON', function (string $notice) {
    config(['app.travel_alert_price_notice' => $notice]);

    $blocks = ldJsonBlocks($this->get('/travel-alert')->assertOk()->getContent());

    expect($blocks)->toHaveCount(3);

    foreach ($blocks as $block) {
        expect(json_decode($block, true))
            ->not->toBeNull(json_last_error_msg());
    }
})->with([
    'mit Hinweis' => 'Bis 31.12.2026 kostenlos.',
    'ohne Hinweis' => '',
]);

test('ohne Hinweis entfaellt das Kostenlos-Angebot in den Structured Data', function () {
    config(['app.travel_alert_price_notice' => '']);

    $blocks = ldJsonBlocks($this->get('/travel-alert')->getContent());
    $application = json_decode($blocks[0], true);

    $prices = array_column($application['offers'], 'price');

    expect($prices)->not->toContain('0')
        ->and($prices)->toContain('5.00');
});

test('mit Hinweis liefert das Kostenlos-Angebot den Text ohne Markup', function () {
    config(['app.travel_alert_price_notice' => 'Bis <strong>31.12.2026</strong> kostenlos.']);

    $blocks = ldJsonBlocks($this->get('/travel-alert')->getContent());
    $application = json_decode($blocks[0], true);

    $freeOffer = collect($application['offers'])->firstWhere('price', '0');

    expect($freeOffer)->not->toBeNull()
        ->and($freeOffer['description'])->toBe('Bis 31.12.2026 kostenlos.');
});

test('die FAQ-Antwort zum Preis kommt ohne Hinweis aus', function () {
    config(['app.travel_alert_price_notice' => '']);

    $blocks = ldJsonBlocks($this->get('/travel-alert')->getContent());
    $faq = collect(json_decode($blocks[2], true)['mainEntity'])
        ->firstWhere('name', 'Was kostet TravelAlert?');

    expect($faq['acceptedAnswer']['text'])->toStartWith('Ab dem 01.07.2026 beträgt');
});

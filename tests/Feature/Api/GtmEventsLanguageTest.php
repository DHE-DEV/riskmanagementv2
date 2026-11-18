<?php

use App\Models\Customer;
use App\Models\CustomEvent;
use Illuminate\Support\Facades\Cache;

/**
 * Sprachauswahl der GTM-API per ?lang=: ohne Parameter Deutsch (Ausgangssprache),
 * mit lang=en/nl die Übersetzung, bei fehlender Übersetzung Deutsch mit language=de.
 */
function gtmLanguageToken(): string
{
    return Customer::factory()->create(['gtm_api_enabled' => true])->createToken('Test', ['gtm:read'])->plainTextToken;
}

function translatedGtmEvent(array $titles, array $contents): CustomEvent
{
    return CustomEvent::create([
        'title_translations' => $titles,
        'popup_content_translations' => $contents,
        'event_type' => 'other',
        'priority' => 'high',
        'start_date' => now()->subDay(),
        'is_active' => true,
        'review_status' => 'approved',
    ]);
}

beforeEach(fn () => Cache::flush());

it('liefert ohne lang oder mit lang=de Deutsch, auch wenn der Client Englisch bevorzugt', function (string $query) {
    translatedGtmEvent(['de' => 'Streik am Flughafen', 'en' => 'Airport strike'], ['de' => '<p>Ausfälle</p>', 'en' => '<p>Cancellations</p>']);

    $response = $this->withToken(gtmLanguageToken())
        ->withHeader('Accept-Language', 'en')
        ->getJson('/api/v1/events'.$query);

    $response->assertOk()
        ->assertHeader('Content-Language', 'de')
        ->assertJsonPath('data.0.title', 'Streik am Flughafen')
        ->assertJsonPath('data.0.description', 'Ausfälle')
        ->assertJsonPath('data.0.language', 'de');
})->with([
    'ohne lang' => [''],
    'lang=de' => ['?lang=de'],
]);

it('liefert mit lang=en Titel und Beschreibung auf Englisch', function () {
    translatedGtmEvent(['de' => 'Streik am Flughafen', 'en' => 'Airport strike'], ['de' => '<p>Ausfälle</p>', 'en' => '<p>Cancellations</p>']);

    $response = $this->withToken(gtmLanguageToken())->getJson('/api/v1/events?lang=en');

    $response->assertOk()
        ->assertHeader('Content-Language', 'en')
        ->assertJsonPath('data.0.title', 'Airport strike')
        ->assertJsonPath('data.0.description', 'Cancellations')
        ->assertJsonPath('data.0.language', 'en');
});

it('kennzeichnet den Rückfall auf Deutsch, wenn die Übersetzung fehlt', function () {
    translatedGtmEvent(['de' => 'Nur auf Deutsch'], ['de' => '<p>Text</p>']);

    $response = $this->withToken(gtmLanguageToken())->getJson('/api/v1/events?lang=nl');

    $response->assertOk()
        ->assertHeader('Content-Language', 'nl')
        ->assertJsonPath('data.0.title', 'Nur auf Deutsch')
        ->assertJsonPath('data.0.language', 'de');
});

it('liefert ein einzelnes Ereignis in der gewünschten Sprache', function () {
    $event = translatedGtmEvent(['de' => 'Streik am Flughafen', 'en' => 'Airport strike'], ['de' => '<p>Ausfälle</p>', 'en' => '<p>Cancellations</p>']);

    $response = $this->withToken(gtmLanguageToken())->getJson('/api/v1/events/'.$event->uuid.'?lang=en');

    $response->assertOk()
        ->assertHeader('Content-Language', 'en')
        ->assertJsonPath('data.title', 'Airport strike');
});

it('lehnt eine nicht unterstützte Sprache ab', function (string $url) {
    $response = $this->withToken(gtmLanguageToken())->getJson($url);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['lang' => 'The selected lang is invalid.']);
})->with([
    'Liste' => ['/api/v1/events?lang=fr'],
    'Umkreis' => ['/api/v1/events/nearby?latitude=52.5&longitude=13.4&radius=100&lang=fr'],
]);

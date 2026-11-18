<?php

use App\Models\CustomEvent;
use Illuminate\Support\Facades\Cache;

/**
 * Sprachauswahl der Ereignis-Feeds (RSS/Atom) per ?lang=: ohne Parameter Deutsch,
 * mit lang=en/nl Titel, Beschreibung und Beschriftungen in dieser Sprache.
 */
function translatedFeedEvent(): CustomEvent
{
    return CustomEvent::create([
        'title_translations' => ['de' => 'Streik am Flughafen', 'en' => 'Airport strike', 'nl' => 'Staking op de luchthaven'],
        'popup_content_translations' => ['de' => '<p>Ausfälle</p>', 'en' => '<p>Cancellations</p>', 'nl' => '<p>Annuleringen</p>'],
        'description' => 'Unübersetzte Kurzbeschreibung',
        'event_type' => 'other',
        'priority' => 'high',
        'start_date' => now()->subDay(),
        'is_active' => true,
        'review_status' => 'approved',
    ]);
}

beforeEach(fn () => Cache::flush());

it('liefert den RSS-Feed ohne lang auf Deutsch, auch wenn der Browser Englisch bevorzugt', function () {
    translatedFeedEvent();

    $response = $this->withHeader('Accept-Language', 'en')->get('/feed/events/all.xml');

    $response->assertOk()
        ->assertHeader('Content-Language', 'de')
        ->assertSee('<language>de-DE</language>', false)
        ->assertSee('Global Travel Monitor - Aktuelle Ereignisse')
        ->assertSee('<title>Streik am Flughafen</title>', false)
        ->assertSee('Priorität: Hoch')
        ->assertSee('Unübersetzte Kurzbeschreibung');
});

it('liefert den RSS-Feed mit lang=en auf Englisch und bevorzugt die übersetzte Beschreibung', function () {
    translatedFeedEvent();

    $response = $this->get('/feed/events/all.xml?lang=en');

    $response->assertOk()
        ->assertHeader('Content-Language', 'en')
        ->assertSee('<language>en</language>', false)
        ->assertSee('Global Travel Monitor - Current events')
        ->assertSee('<title>Airport strike</title>', false)
        ->assertSee('Priority: High')
        ->assertSee('Cancellations')
        ->assertDontSee('Unübersetzte Kurzbeschreibung');
});

it('liefert den Atom-Feed mit lang=nl auf Niederländisch', function () {
    translatedFeedEvent();

    $response = $this->get('/feed/events/all.atom?lang=nl');

    $response->assertOk()
        ->assertHeader('Content-Language', 'nl')
        ->assertSee('xml:lang="nl-NL"', false)
        ->assertSee('<title>Staking op de luchthaven</title>', false)
        ->assertSee('Prioriteit: Hoog');
});

it('hält die Sprachen im Feed-Cache getrennt', function () {
    translatedFeedEvent();

    $this->get('/feed/events/all.xml')->assertSee('Streik am Flughafen');
    $response = $this->get('/feed/events/all.xml?lang=en');

    $response->assertOk()
        ->assertSee('Airport strike')
        ->assertDontSee('Streik am Flughafen');
});

it('übersetzt auch die gefilterten Feeds', function () {
    translatedFeedEvent();

    $response = $this->get('/feed/events/priority/high.xml?lang=en');

    $response->assertOk()
        ->assertSee('Events - Priority High')
        ->assertSee('Airport strike');
});

it('lehnt eine nicht unterstützte Sprache ab', function () {
    $response = $this->get('/feed/events/all.xml?lang=fr');

    $response->assertBadRequest()
        ->assertSee('Unsupported language: fr. Supported: de, en, nl');
});

it('leert den Feed-Cache jeder Sprache, wenn sich ein Ereignis ändert', function () {
    config(['app.event_auto_translate' => false]);
    $event = translatedFeedEvent();
    $this->get('/feed/events/all.xml?lang=en')->assertSee('Airport strike');

    $event->update(['title_translations' => ['de' => 'Streik am Flughafen', 'en' => 'Strike ended', 'nl' => 'Staking op de luchthaven']]);

    $this->get('/feed/events/all.xml?lang=en')
        ->assertOk()
        ->assertSee('Strike ended');
});

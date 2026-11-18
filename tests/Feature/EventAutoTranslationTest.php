<?php

use App\Models\Customer;
use App\Models\CustomEvent;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

/**
 * Automatische DeepL-Übersetzung: freigegebene globale Ereignisse bekommen fehlende
 * Sprachen im Hintergrund; manuelle Übersetzungen, Entwürfe und Kunden-Ereignisse
 * bleiben unberührt. Dazu der Befehl events:translate für den Bestand.
 */
function fakeDeepL(): void
{
    config(['services.deepl.api_key' => 'test-key', 'services.deepl.api_url' => 'https://api.deepl.test/v2/translate']);

    Http::fake([
        'api.deepl.test/*' => fn (Request $request) => Http::response([
            'translations' => [['text' => '['.$request['target_lang'].'] '.$request['text']]],
        ]),
    ]);
}

function germanEvent(array $attributes = []): CustomEvent
{
    return CustomEvent::create(array_merge([
        'title_translations' => ['de' => 'Streik am Flughafen'],
        'popup_content_translations' => ['de' => '<p>Ausfälle</p>'],
        'event_type' => 'other',
        'priority' => 'high',
        'start_date' => now()->subDay(),
        'is_active' => true,
        'review_status' => 'approved',
    ], $attributes));
}

it('übersetzt ein neues freigegebenes Ereignis in alle fehlenden Sprachen', function () {
    fakeDeepL();

    $event = germanEvent(['title_translations' => ['de' => 'Streik am Flughafen', 'en' => 'Manueller Titel']]);

    $event->refresh();
    expect($event->title_translations)->toBe([
        'de' => 'Streik am Flughafen',
        'en' => 'Manueller Titel',
        'nl' => '[NL] Streik am Flughafen',
    ])->and($event->popup_content_translations)->toBe([
        'de' => '<p>Ausfälle</p>',
        'en' => '[EN-GB] <p>Ausfälle</p>',
        'nl' => '[NL] <p>Ausfälle</p>',
    ]);
});

it('übersetzt ungeprüfte Entwürfe erst bei der Freigabe', function () {
    fakeDeepL();
    $event = germanEvent(['review_status' => 'pending_review']);
    Http::assertNothingSent();

    $event->update(['review_status' => 'approved']);

    expect($event->refresh()->title_translations['en'])->toBe('[EN-GB] Streik am Flughafen');
});

it('übersetzt keine Ereignisse von Kunden', function () {
    fakeDeepL();

    germanEvent(['customer_id' => Customer::factory()->create()->id]);

    Http::assertNothingSent();
});

it('übersetzt nicht, wenn die automatische Übersetzung abgeschaltet ist', function () {
    fakeDeepL();
    config(['app.event_auto_translate' => false]);

    germanEvent();

    Http::assertNothingSent();
});

it('übersetzt mit events:translate laufende und künftige Ereignisse, aber keine beendeten', function () {
    config(['app.event_auto_translate' => false]);
    $running = germanEvent(['end_date' => now()->addWeek()]);
    $upcoming = germanEvent(['start_date' => now()->addWeek(), 'end_date' => null]);
    $ended = germanEvent(['end_date' => now()->subWeek()]);
    fakeDeepL();

    $this->artisan('events:translate')
        ->expectsOutputToContain('2 Ereignis(se) mit fehlenden Übersetzungen')
        ->expectsOutputToContain('8 Feld(er) übersetzt.')
        ->assertSuccessful();

    expect($running->refresh()->title_translations['nl'])->toBe('[NL] Streik am Flughafen')
        ->and($upcoming->refresh()->popup_content_translations['en'])->toBe('[EN-GB] <p>Ausfälle</p>')
        ->and($ended->refresh()->title_translations)->toBe(['de' => 'Streik am Flughafen']);
});

it('schickt bei events:translate --dry-run nichts an DeepL', function () {
    config(['app.event_auto_translate' => false]);
    germanEvent();
    fakeDeepL();

    $this->artisan('events:translate --dry-run')
        ->expectsOutputToContain('1 Ereignis(se) mit fehlenden Übersetzungen')
        ->assertSuccessful();

    Http::assertNothingSent();
});

it('meldet fehlgeschlagene Übersetzungen und behält die gelungenen', function () {
    config(['app.event_auto_translate' => false]);
    $event = germanEvent();
    config(['services.deepl.api_key' => 'test-key', 'services.deepl.api_url' => 'https://api.deepl.test/v2/translate']);
    Http::fake([
        'api.deepl.test/*' => fn (Request $request) => $request['target_lang'] === 'NL'
            ? Http::response(['message' => 'Quota exceeded'], 456)
            : Http::response(['translations' => [['text' => 'EN: '.$request['text']]]]),
    ]);

    $this->artisan('events:translate')
        ->expectsOutputToContain("#{$event->id}: NL: DeepL-API-Fehler (HTTP 456).")
        ->assertFailed();

    expect($event->refresh()->title_translations)->toBe(['de' => 'Streik am Flughafen', 'en' => 'EN: Streik am Flughafen']);
});

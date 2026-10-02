<?php

use App\Livewire\AdminV2\Events\Editor;
use App\Models\CustomEvent;
use App\Models\CustomEventSourceCheck;
use App\Models\User;
use App\Services\EventSourceCheckService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

/**
 * KI-Pruefung der Quellen: passt der erfasste Stand eines Ereignisses noch zu
 * dem, was die Quelle heute sagt?
 *
 * Die Quelle steht unter einer oeffentlichen IP-Adresse, damit der Test ohne
 * Namensaufloesung auskommt.
 */
const SOURCE_URL = 'https://93.184.216.34/meldung';

function eventWithSource(): CustomEvent
{
    return CustomEvent::create([
        'title' => 'Streik im Nahverkehr in Rom',
        'popup_content' => '<p>Am 9. Oktober stehen Busse und Bahnen still.</p>',
        'event_type' => 'other',
        'priority' => 'medium',
        'start_date' => '2026-10-09 00:00:00',
        'end_date' => '2026-10-09 23:59:00',
        'is_active' => true,
        'archived' => false,
        'review_status' => 'approved',
        'source_links' => [['show_frontend' => true, 'link_text' => 'Verkehrsbetriebe', 'link_url' => SOURCE_URL]],
    ]);
}

function fakeSourceAndAi(string $aiAnswer, ?string $page = null): void
{
    config(['services.openai.key' => 'test-key']);

    Http::fake([
        '93.184.216.34/*' => Http::response($page ?? '<html><head><script>track()</script></head><body><nav>Menü</nav><article><h1>Streik verlängert</h1><p>'
            .str_repeat('Der Streik im römischen Nahverkehr wird bis zum 11. Oktober verlängert. ', 6).'</p></article></body></html>'),
        'api.openai.com/*' => Http::response([
            'model' => 'gpt-4o-2024-08-06',
            'choices' => [['message' => ['content' => $aiAnswer]]],
            'usage' => ['prompt_tokens' => 3000, 'completion_tokens' => 500, 'total_tokens' => 3500],
        ]),
    ]);
}

beforeEach(function () {
    $this->actingAs(User::factory()->create(['name' => 'Anna', 'is_admin' => true, 'is_active' => true]));
});

it('meldet eine geaenderte Situation und haelt das Ergebnis am Ereignis fest', function () {
    fakeSourceAndAi("```json\n".json_encode([
        'status' => 'changed',
        'summary' => 'Die Quelle nennt eine Verlängerung des Streiks.',
        'changes' => ['Streik jetzt bis 11. Oktober statt nur am 9. Oktober'],
        'suggestion' => 'Enddatum und Beschreibung anpassen.',
    ])."\n```");

    $event = eventWithSource();

    Livewire::test(Editor::class, ['event' => $event->id])
        ->call('checkSource', 0)
        ->assertSee('Die Situation hat sich möglicherweise geändert')
        ->assertSee('Die Quelle nennt eine Verlängerung des Streiks.')
        ->assertSee('Streik jetzt bis 11. Oktober statt nur am 9. Oktober')
        ->assertSee('Enddatum und Beschreibung anpassen.');

    // Die KI bekommt das Ereignis und den Text der Quelle – ohne Skripte und Navigation.
    Http::assertSent(function (Request $request) {
        if (! str_contains($request->url(), 'api.openai.com')) {
            return false;
        }

        $prompt = $request['messages'][0]['content'];

        return str_contains($prompt, 'Streik im Nahverkehr in Rom')
            && str_contains($prompt, '09.10.2026 bis 09.10.2026')
            && str_contains($prompt, 'wird bis zum 11. Oktober verlängert')
            && ! str_contains($prompt, 'track()')
            && ! str_contains($prompt, 'Menü');
    });

    $check = CustomEventSourceCheck::sole();

    expect($check->custom_event_id)->toBe($event->id)
        ->and($check->status)->toBe('changed')
        ->and($check->url)->toBe(SOURCE_URL)
        ->and($check->changes)->toBe(['Streik jetzt bis 11. Oktober statt nur am 9. Oktober']);

    // Beim naechsten Oeffnen steht das letzte Ergebnis wieder da.
    Livewire::test(Editor::class, ['event' => $event->id])
        ->assertSee('Die Quelle nennt eine Verlängerung des Streiks.')
        ->assertSee('durch Anna');
});

it('schlaegt Verbesserungen je Feld vor und uebernimmt sie auf Wunsch ins Formular', function () {
    $strike = \App\Models\EventType::create(['code' => 'strike', 'name' => 'Streik', 'icon' => 'fa-bolt', 'is_active' => true, 'sort_order' => 1]);
    $travel = \App\Models\EventType::create(['code' => 'travel', 'name' => 'Reiseverkehr', 'icon' => 'fa-plane', 'is_active' => true, 'sort_order' => 2]);

    fakeSourceAndAi(json_encode([
        'status' => 'changed',
        'summary' => 'Der Streik wurde verlängert.',
        'changes' => ['Ende jetzt 11. Oktober'],
        'suggestion' => '',
        'proposals' => [
            'title' => ['value' => 'Streik im Nahverkehr in Rom bis 11. Oktober', 'reason' => 'Die Quelle nennt die Verlängerung.'],
            'description' => ['value' => "Der Streik dauert bis zum 11. Oktober.\n\nBusse und Bahnen fahren nicht.", 'reason' => 'Neuer Zeitraum.'],
            'event_types' => ['value' => ['streik', 'Reiseverkehr', 'Erfundener Typ'], 'reason' => 'Es geht um einen Streik im Verkehr.'],
            'priority' => ['value' => 'high', 'reason' => 'Mehrtägiger Ausfall.'],
            'period' => ['start' => '2026-10-09', 'end' => '2026-10-11', 'reason' => 'Verlängerung laut Quelle.'],
            'locations' => ['value' => ['Italien – Latium – Rom'], 'reason' => 'Unverändert Rom.'],
        ],
    ]));

    $event = eventWithSource();
    $event->eventTypes()->attach($travel->id);
    $hash = CustomEventSourceCheck::hashFor(SOURCE_URL);

    $editor = Livewire::test(Editor::class, ['event' => $event->id])
        ->call('checkSource', 0)
        ->assertSee('Vorschläge der KI')
        ->assertSee('Streik im Nahverkehr in Rom bis 11. Oktober')
        ->assertSee('Die Quelle nennt die Verlängerung.')
        // Unbekannte Typen verwirft die Auswertung, bekannte stehen in der Schreibweise der Stammdaten da.
        ->assertSee('Streik, Reiseverkehr')
        ->assertDontSee('Erfundener Typ')
        ->assertSee('09.10.2026 bis 11.10.2026');

    // Die KI sieht auch Typen und Prioritaet des Ereignisses und die zulaessigen Typen.
    Http::assertSent(fn (Request $request) => str_contains($request->url(), 'api.openai.com')
        && str_contains($request['messages'][0]['content'], 'Event-Typen: Reiseverkehr')
        && str_contains($request['messages'][0]['content'], 'Priorität: Mittel')
        && str_contains($request['messages'][0]['content'], 'Zulässige Event-Typen: Reiseverkehr, Streik'));

    $proposals = collect($editor->get('sourceChecks')[$hash]['proposals'])->pluck('field')->all();

    expect($proposals)->toBe(['title', 'description', 'event_types', 'priority', 'period', 'locations']);

    // Uebernehmen fuellt nur das Formular – gespeichert wird erst auf Wunsch.
    foreach (array_keys($proposals) as $index) {
        $editor->call('applyProposal', $hash, $index);

        // Die neue Beschreibung geht zusaetzlich an den Texteditor im Browser.
        if ($proposals[$index] === 'description') {
            $editor->assertDispatched('adminv2-editor-set', locale: 'de');
        }
    }

    $editor->assertSet('titles.de', 'Streik im Nahverkehr in Rom bis 11. Oktober')
        ->assertSet('contents.de', '<p>Der Streik dauert bis zum 11. Oktober.</p><p>Busse und Bahnen fahren nicht.</p>')
        ->assertSet('eventTypeIds', [(string) $travel->id, (string) $strike->id])
        ->assertSet('priority', 'high')
        ->assertSet('startDate', '2026-10-09T00:00')
        ->assertSet('endDate', '2026-10-11T23:59')
        // Standorte bleiben ein Hinweis und werden nicht automatisch uebernommen.
        ->assertSee('Bitte oben über die Suche anpassen');

    expect($event->fresh()->getTitle('de'))->toBe('Streik im Nahverkehr in Rom')
        ->and(CustomEventSourceCheck::sole()->proposals)->toHaveCount(6);

    // Rueckgaengig stellt je Feld den Stand vor dem Uebernehmen wieder her.
    foreach (array_keys($proposals) as $index) {
        $editor->call('undoProposal', $hash, $index);

        if ($proposals[$index] === 'description') {
            $editor->assertDispatched('adminv2-editor-set', locale: 'de', html: '<p>Am 9. Oktober stehen Busse und Bahnen still.</p>');
        }
    }

    $editor->assertSet('titles.de', 'Streik im Nahverkehr in Rom')
        ->assertSet('contents.de', '<p>Am 9. Oktober stehen Busse und Bahnen still.</p>')
        ->assertSet('eventTypeIds', [(string) $travel->id])
        ->assertSet('priority', 'medium')
        ->assertSet('startDate', '2026-10-09T00:00')
        ->assertSet('endDate', '2026-10-09T23:59')
        // Danach laesst sich der Vorschlag erneut uebernehmen.
        ->assertDontSee('Rückgängig')
        ->call('applyProposal', $hash, 0)
        ->assertSet('titles.de', 'Streik im Nahverkehr in Rom bis 11. Oktober')
        ->assertSee('Rückgängig');
});

it('zeigt Verbrauch und Kosten der KI-Abfrage', function () {
    \App\Models\SystemSetting::flushResolved();
    \App\Models\SystemSetting::write(\App\Support\AiSettings::KEY_MODEL, 'gpt-4o');
    // Ohne Preisliste: zunaechst ist fuer das Modell kein Preis bekannt.
    config(['ai_prices.models' => []]);

    fakeSourceAndAi(json_encode(['status' => 'unchanged', 'summary' => 'Passt.', 'changes' => [], 'suggestion' => '']));

    $event = eventWithSource();

    // Ohne hinterlegten Preis: Token ja, Kosten nein.
    Livewire::test(Editor::class, ['event' => $event->id])
        ->call('checkSource', 0)
        ->assertSee('3.500 Token')
        ->assertSee('3.000 Eingabe, 500 Ausgabe')
        ->assertSee('kein Preis für dieses Modell hinterlegt');

    // Mit Preis: 3.000 x 2,50 $ + 500 x 10 $ je 1 Mio. Token = 0,0125 $.
    \App\Support\AiSettings::setPrices('gpt-4o', 2.5, 10.0);

    Livewire::test(Editor::class, ['event' => $event->id])
        ->call('checkSource', 0)
        ->assertSee('Kosten ca. 0,0125 $');

    $check = CustomEventSourceCheck::latest('id')->first();

    expect($check->model)->toBe('gpt-4o-2024-08-06')
        ->and($check->input_tokens)->toBe(3000)
        ->and($check->output_tokens)->toBe(500)
        ->and($check->cost)->toBe(0.0125);

    // Nach dem Neuladen stehen Verbrauch und Kosten weiter am Ergebnis.
    Livewire::test(Editor::class, ['event' => $event->id])->assertSee('Kosten ca. 0,0125 $');
});

it('zeigt an, wenn die Quelle den erfassten Stand stuetzt', function () {
    fakeSourceAndAi(json_encode(['status' => 'unchanged', 'summary' => 'Die Quelle bestätigt den Streik am 9. Oktober.', 'changes' => [], 'suggestion' => '']));

    Livewire::test(Editor::class, ['event' => eventWithSource()->id])
        ->call('checkSource', 0)
        ->assertSee('Keine Änderung erkannt')
        ->assertSee('Die Quelle bestätigt den Streik am 9. Oktober.');
});

it('fragt die KI nicht, wenn die Quelle kaum Text liefert', function () {
    fakeSourceAndAi('{}', '<html><body><div id="app"></div><script>render()</script></body></html>');

    Livewire::test(Editor::class, ['event' => eventWithSource()->id])
        ->call('checkSource', 0)
        ->assertSee('Aus der Quelle nicht eindeutig zu beurteilen');

    Http::assertNotSent(fn (Request $request) => str_contains($request->url(), 'api.openai.com'));
});

it('ruft keine internen Adressen ab', function () {
    Http::fake();

    $service = app(EventSourceCheckService::class);

    foreach (['http://127.0.0.1/admin', 'http://169.254.169.254/latest/meta-data', 'http://10.0.0.5/', 'ftp://93.184.216.34/datei', 'javascript:alert(1)'] as $url) {
        expect($service->isPublicHttpUrl($url))->toBeFalse();
        expect($service->check(['title' => 'x', 'description' => 'x', 'period' => 'x', 'locations' => 'x'], $url)['status'])->toBe('error');
    }

    expect($service->isPublicHttpUrl(SOURCE_URL))->toBeTrue();

    Http::assertNothingSent();
});

it('meldet einen Fehler, wenn die Quelle nicht erreichbar ist', function () {
    config(['services.openai.key' => 'test-key']);
    Http::fake(['93.184.216.34/*' => Http::response('Not found', 404)]);

    Livewire::test(Editor::class, ['event' => eventWithSource()->id])
        ->call('checkSource', 0)
        ->assertSee('Prüfung nicht möglich')
        ->assertSee('Status 404');

    // Ein fehlgeschlagener Versuch wird nicht als Befund gespeichert.
    expect(CustomEventSourceCheck::count())->toBe(0);
});

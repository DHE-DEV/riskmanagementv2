<?php

use App\Jobs\RunAiEventSearch;
use App\Livewire\AdminV2\Events\Index;
use App\Livewire\AdminV2\System\Ai;
use App\Models\AiEventSearch;
use App\Models\AiEventSuggestion;
use App\Models\Country;
use App\Models\CustomEvent;
use App\Models\EventType;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\AiEventSearchService;
use App\Support\AiSettings;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

/**
 * KI-Suche nach aktuellen Ereignissen: Auftrag und Ausschluss unter
 * System > KI, Vorschlaege im Reiter "Heute angelegt", Entwurf aus einem
 * Vorschlag – und kein Thema doppelt, nur weil mehrere Quellen berichten.
 */
function aiEvent(array $attributes = []): array
{
    return array_merge([
        'title' => 'Streik legt Nahverkehr in Rom lahm',
        'summary' => 'In Rom streiken Bus- und Bahnpersonal. Reisende müssen mit Ausfällen rechnen.',
        'priority' => 'medium',
        'start_date' => '2026-10-03',
        'end_date' => '2026-10-04',
        'event_types' => ['strike', 'travel'],
        'countries' => ['IT'],
        'location' => 'Rom',
        'sources' => [['title' => 'ANSA', 'url' => 'https://ansa.example/rom-streik']],
    ], $attributes);
}

/**
 * Antwort der Responses-API mit Websuche. Die Antwort laesst sich innerhalb
 * eines Tests wechseln (der Fake selbst wird in beforeEach einmal gesetzt).
 */
function fakeAiSearch(array $events): void
{
    $GLOBALS['aiSearchResponse'] = fn () => Http::response([
        'model' => 'gpt-4o-2024-08-06',
        'output' => [
            ['type' => 'web_search_call', 'status' => 'completed'],
            ['type' => 'message', 'content' => [['type' => 'output_text', 'text' => "```json\n".json_encode(['events' => $events])."\n```"]]],
        ],
        'usage' => ['input_tokens' => 12000, 'output_tokens' => 1500, 'total_tokens' => 13500],
    ]);
}

function runAiSearch(bool $excludeExisting = true): AiEventSearch
{
    $search = AiEventSearch::create(['status' => AiEventSearch::STATUS_RUNNING, 'exclude_existing' => $excludeExisting]);

    return app(AiEventSearchService::class)->run($search)->fresh();
}

beforeEach(function () {
    Carbon::setTestNow('2026-10-02 10:00:00');
    SystemSetting::flushResolved();

    fakeAiSearch([]);
    Http::fake(['api.openai.com/v1/responses' => fn () => ($GLOBALS['aiSearchResponse'])()]);

    SystemSetting::write(AiSettings::KEY_API_KEY, 'sk-test-1234567890', encrypted: true);
    SystemSetting::write(AiSettings::KEY_MODEL, 'gpt-4o');

    $this->italy = Country::factory()->create(['iso_code' => 'IT', 'iso3_code' => 'ITA', 'name_translations' => ['de' => 'Italien', 'en' => 'Italy']]);
    $this->greece = Country::factory()->create(['iso_code' => 'GR', 'iso3_code' => 'GRC', 'name_translations' => ['de' => 'Griechenland', 'en' => 'Greece']]);

    foreach ([['strike', 'Streik'], ['travel', 'Reiseverkehr'], ['environment', 'Umweltereignisse']] as $index => [$code, $name]) {
        EventType::create(['code' => $code, 'name' => $name, 'icon' => 'fa-bolt', 'is_active' => true, 'sort_order' => $index + 1]);
    }

    $this->actingAs($this->admin = User::factory()->create(['name' => 'Anna', 'is_admin' => true, 'is_active' => true]));
});

afterEach(fn () => Carbon::setTestNow());

it('sucht mit Websuche, schlaegt Einordnung vor und fuehrt jedes Ereignis nur einmal', function () {
    AiSettings::setPrices('gpt-4o', 2.5, 10.0);

    fakeAiSearch([
        aiEvent(),
        // Dasselbe Ereignis aus einer anderen Quelle, anders formuliert.
        aiEvent(['title' => 'Rom: Streik im Nahverkehr legt Busse und Bahnen lahm', 'sources' => [['title' => 'Tagesschau', 'url' => 'https://tagesschau.example/rom']]]),
        // Dieselbe Quelle, anderer Titel – ebenfalls dasselbe.
        aiEvent(['title' => 'Ausstand in der italienischen Hauptstadt', 'sources' => [['title' => 'ANSA', 'url' => 'https://ansa.example/rom-streik']]]),
        aiEvent([
            'title' => 'Waldbrände auf Rhodos', 'summary' => 'Auf Rhodos brennen mehrere Wälder.', 'priority' => 'high',
            'start_date' => '2026-10-01', 'end_date' => null, 'event_types' => ['environment', 'gibt-es-nicht'],
            'countries' => ['GR', 'XX'], 'location' => 'Rhodos',
            'sources' => [['title' => 'Kathimerini', 'url' => 'https://ekathimerini.example/rhodos'], ['title' => 'Unsinn', 'url' => 'javascript:alert(1)']],
        ]),
        ['title' => ''],
    ]);

    $search = runAiSearch();

    expect($search->status)->toBe(AiEventSearch::STATUS_DONE)
        ->and($search->found_count)->toBe(4)
        ->and($search->new_count)->toBe(2)
        ->and($search->input_tokens)->toBe(12000)
        // 12.000 x 2,50 $ + 1.500 x 10 $ je 1 Mio. Token
        ->and($search->cost)->toBe(0.045);

    $rome = AiEventSuggestion::firstWhere('title', 'Streik legt Nahverkehr in Rom lahm');
    $rhodes = AiEventSuggestion::firstWhere('title', 'Waldbrände auf Rhodos');

    expect(AiEventSuggestion::count())->toBe(2)
        // Alle Quellen desselben Ereignisses stehen an EINEM Vorschlag.
        ->and(array_column($rome->sources, 'url'))->toBe(['https://ansa.example/rom-streik', 'https://tagesschau.example/rom'])
        ->and($rome->priority)->toBe('medium')
        ->and($rome->event_type_codes)->toBe(['strike', 'travel'])
        ->and($rome->country_codes)->toBe(['IT'])
        ->and($rome->start_date->format('Y-m-d'))->toBe('2026-10-03')
        // Unbekannte Typen, Laender und unbrauchbare Adressen fallen weg.
        ->and($rhodes->event_type_codes)->toBe(['environment'])
        ->and($rhodes->country_codes)->toBe(['GR'])
        ->and($rhodes->end_date)->toBeNull()
        ->and(array_column($rhodes->sources, 'url'))->toBe(['https://ekathimerini.example/rhodos']);

    // Der Auftrag geht mit Websuche und dem hinterlegten Modell raus.
    Http::assertSent(fn (Request $request) => $request->url() === 'https://api.openai.com/v1/responses'
        && $request['model'] === 'gpt-4o'
        && $request['tools'] === [['type' => 'web_search']]
        && str_contains($request['input'], 'Heute ist der 02.10.2026')
        && str_contains($request['input'], '- strike: Streik')
        && str_contains($request['input'], 'jedes Ereignis nur einmal'));

    // Zweite Suche: dasselbe Thema aus neuer Quelle kommt nicht erneut – die Quelle wird ergaenzt.
    fakeAiSearch([aiEvent(['title' => 'Nahverkehrsstreik in Rom', 'sources' => [['title' => 'Repubblica', 'url' => 'https://repubblica.example/sciopero']]])]);

    expect(runAiSearch()->new_count)->toBe(0)
        ->and(AiEventSuggestion::count())->toBe(2)
        ->and($rome->fresh()->sources)->toHaveCount(3);

    // Verworfenes kommt ebenfalls nicht wieder.
    $rhodes->update(['status' => AiEventSuggestion::STATUS_DISMISSED]);
    fakeAiSearch([aiEvent(['title' => 'Waldbrände auf Rhodos breiten sich aus', 'countries' => ['GR'], 'location' => 'Insel Rhodos', 'event_types' => ['environment'], 'start_date' => '2026-10-02', 'end_date' => null, 'sources' => []])]);

    expect(runAiSearch()->new_count)->toBe(0);

    // Gleiches Land, gleiche Tage, gleicher Typ – aber ein anderer Ort: ein eigenes Thema.
    fakeAiSearch([aiEvent(['title' => 'Streik legt Nahverkehr in Mailand lahm', 'location' => 'Mailand', 'sources' => []])]);

    expect(runAiSearch()->new_count)->toBe(1);
});

it('schliesst bereits erfasste Ereignisse auf Wunsch aus', function () {
    $event = CustomEvent::create([
        'title' => 'Streik im Nahverkehr in Rom', 'popup_content' => 'Text', 'event_type' => 'other', 'priority' => 'medium',
        'start_date' => '2026-10-03 00:00:00', 'end_date' => '2026-10-04 23:59:00',
        'is_active' => true, 'archived' => false, 'review_status' => 'approved',
    ]);
    $event->countries()->attach($this->italy->id, ['use_default_coordinates' => true]);

    $service = app(AiEventSearchService::class);

    // Der Auftrag nennt der KI, was es schon gibt – nur wenn der Ausschluss an ist.
    expect($service->buildPrompt(true))->toContain('bereits erfasst')->toContain('- Streik im Nahverkehr in Rom (IT)')
        ->and($service->buildPrompt(false))->not->toContain('Streik im Nahverkehr in Rom');

    // Liefert die KI es trotzdem, wird es aussortiert.
    fakeAiSearch([aiEvent()]);

    expect(runAiSearch(excludeExisting: true)->new_count)->toBe(0)
        ->and(AiEventSuggestion::count())->toBe(0);

    // Ohne Ausschluss wird es vorgeschlagen.
    expect(runAiSearch(excludeExisting: false)->new_count)->toBe(1);

    // Ein gleichnamiges Ereignis in einem anderen Land ist ein anderes Thema.
    AiEventSuggestion::query()->delete();
    fakeAiSearch([aiEvent(['title' => 'Streik im Nahverkehr in Athen', 'countries' => ['GR'], 'location' => 'Athen', 'sources' => []])]);

    expect(runAiSearch(excludeExisting: true)->new_count)->toBe(1);

    // Auch im selben Land ist ein anderer Ort ein anderes Thema.
    fakeAiSearch([aiEvent(['title' => 'Streik im Nahverkehr in Mailand', 'location' => 'Mailand', 'sources' => []])]);

    expect(runAiSearch(excludeExisting: true)->new_count)->toBe(1);
});

it('haelt einen Fehler der KI am Suchlauf fest', function () {
    $GLOBALS['aiSearchResponse'] = fn () => Http::response(['error' => ['message' => 'You exceeded your current quota.']], 429);

    $search = runAiSearch();

    expect($search->status)->toBe(AiEventSearch::STATUS_FAILED)
        ->and($search->error)->toContain('You exceeded your current quota.')
        ->and(AiEventSuggestion::count())->toBe(0);

    // Eine Antwort ohne auswertbares JSON ist ebenfalls ein Fehler.
    $GLOBALS['aiSearchResponse'] = fn () => Http::response(['output' => [['type' => 'message', 'content' => [['type' => 'output_text', 'text' => 'Leider nichts gefunden.']]]]]);

    expect(runAiSearch()->status)->toBe(AiEventSearch::STATUS_FAILED);
});

it('laesst Auftrag und Ausschluss unter System > KI einstellen und die Suche starten', function () {
    Bus::fake();

    $this->get(route('adminv2.system.ai'))->assertOk()->assertSee('Aktuelle Ereignisse suchen');

    $page = Livewire::test(Ai::class)
        // Der Standard-Auftrag ist bereits hinterlegt.
        ->assertSet('eventSearchPrompt', AiSettings::DEFAULT_EVENT_SEARCH_PROMPT)
        ->assertSet('eventSearchExcludeExisting', true)
        ->set('eventSearchPrompt', 'zu kurz')
        ->call('saveEventSearchSettings')
        ->assertHasErrors('eventSearchPrompt')
        ->set('eventSearchPrompt', 'Suche nach Streiks im europäischen Bahnverkehr der kommenden Woche.')
        ->set('eventSearchExcludeExisting', false)
        ->call('saveEventSearchSettings')
        ->assertHasNoErrors();

    expect(AiSettings::eventSearchPrompt())->toBe('Suche nach Streiks im europäischen Bahnverkehr der kommenden Woche.')
        ->and(AiSettings::eventSearchExcludesExisting())->toBeFalse()
        ->and(app(AiEventSearchService::class)->buildPrompt(false))->toContain('Suche nach Streiks im europäischen Bahnverkehr');

    // Suche starten: der Lauf wird angelegt und nach der Antwort ausgefuehrt.
    $page->call('searchEventsNow')->assertSee('Die KI sucht nach aktuellen Ereignissen');

    $search = AiEventSearch::first();

    expect($search->status)->toBe(AiEventSearch::STATUS_RUNNING)
        ->and($search->exclude_existing)->toBeFalse()
        ->and($search->started_by)->toBe($this->admin->id);

    Bus::assertDispatchedAfterResponse(RunAiEventSearch::class, fn (RunAiEventSearch $job) => $job->searchId === $search->id);

    // Waehrend eine Suche laeuft, startet keine zweite.
    $page->call('searchEventsNow');
    expect(AiEventSearch::count())->toBe(1);

    // Ein haengengebliebener Lauf gilt nach zehn Minuten als abgebrochen.
    Carbon::setTestNow('2026-10-02 10:15:00');
    Livewire::test(Ai::class)->assertSee('abgebrochen')->call('searchEventsNow');
    expect(AiEventSearch::count())->toBe(2);

    // Standard wiederherstellen
    Livewire::test(Ai::class)->call('resetEventSearchPrompt')->call('saveEventSearchSettings');
    expect(AiSettings::eventSearchPrompt())->toBe(AiSettings::DEFAULT_EVENT_SEARCH_PROMPT);
});

it('fuehrt den Suchlauf als Auftrag aus', function () {
    fakeAiSearch([aiEvent()]);

    $search = AiEventSearch::create(['status' => AiEventSearch::STATUS_RUNNING, 'exclude_existing' => true]);

    (new RunAiEventSearch($search->id))->handle(app(AiEventSearchService::class));

    expect($search->fresh()->status)->toBe(AiEventSearch::STATUS_DONE)
        ->and(AiEventSuggestion::count())->toBe(1);
});

it('zeigt die Vorschlaege im Reiter "Heute angelegt" und legt daraus einen Entwurf an', function () {
    Bus::fake();
    fakeAiSearch([aiEvent()]);
    runAiSearch();

    // Ein aelterer offener Vorschlag
    $old = AiEventSuggestion::create(['title' => 'Älteres Thema', 'priority' => 'low', 'country_codes' => ['GR']]);
    $old->forceFill(['created_at' => now()->subDays(2)])->saveQuietly();

    $suggestion = AiEventSuggestion::firstWhere('title', 'Streik legt Nahverkehr in Rom lahm');

    // In den anderen Reitern stehen keine Vorschlaege.
    Livewire::test(Index::class)->assertDontSee('KI-Vorschläge');

    $list = Livewire::test(Index::class)
        ->set('tab', 'today')
        ->assertSee('KI-Vorschläge')
        ->assertSee('Streik legt Nahverkehr in Rom lahm')
        // Die vorgeschlagenen Event-Typen stehen als Marken am Vorschlag.
        ->assertSeeHtml('opacity-60" aria-hidden="true"></i>
                                            Reiseverkehr')
        ->assertSee('Mittel')
        ->assertSee('Streik')
        ->assertSee('Reiseverkehr')
        ->assertSee('03.10.2026 – 04.10.2026')
        ->assertSee('Italien')
        ->assertSee('Rom')
        ->assertSee('https://ansa.example/rom-streik')
        ->assertSee('Als Entwurf anlegen')
        ->assertDontSee('Älteres Thema')
        ->assertSee('Auch ältere zeigen (1)')
        ->set('showOlderSuggestions', true)
        ->assertSee('Älteres Thema');

    // Verwerfen
    $list->call('dismissSuggestion', $old->id)->assertDontSee('Älteres Thema');
    expect($old->fresh()->status)->toBe(AiEventSuggestion::STATUS_DISMISSED);

    // Suche aus der Liste starten
    $list->call('startAiSearch');
    Bus::assertDispatchedAfterResponse(RunAiEventSearch::class);

    // (Die gestartete Suche ist inzwischen fertig.)
    AiEventSearch::query()->where('status', AiEventSearch::STATUS_RUNNING)->update(['status' => AiEventSearch::STATUS_DONE, 'finished_at' => now()]);

    // Als Entwurf anlegen
    $list->call('createDraftFromSuggestion', $suggestion->id);

    $event = CustomEvent::first();

    $list->assertRedirect(route('adminv2.events.edit', $event));

    expect($event->getTitle('de'))->toBe('Streik legt Nahverkehr in Rom lahm')
        ->and($event->is_active)->toBeFalse()
        ->and($event->priority)->toBe('medium')
        ->and($event->start_date->format('Y-m-d H:i'))->toBe('2026-10-03 00:00')
        ->and($event->end_date->format('Y-m-d H:i'))->toBe('2026-10-04 23:59')
        ->and($event->getPopupContent('de'))->toContain('In Rom streiken Bus- und Bahnpersonal.')
        ->and($event->eventTypes()->pluck('code')->sort()->values()->all())->toBe(['strike', 'travel'])
        ->and($event->countries()->pluck('iso_code')->all())->toBe(['IT'])
        ->and($event->normalizedSourceLinks()[0]['link_url'])->toBe('https://ansa.example/rom-streik')
        ->and($event->created_by)->toBe($this->admin->id)
        ->and($event->version_internal_note)->toContain('KI-Vorschlag')
        ->and($suggestion->fresh()->status)->toBe(AiEventSuggestion::STATUS_CONVERTED)
        ->and($suggestion->fresh()->custom_event_id)->toBe($event->id);

    // Der Entwurf steht jetzt unter "Heute angelegt", der Vorschlag ist erledigt.
    Livewire::test(Index::class)
        ->set('tab', 'today')
        ->assertSee('Es gibt keine offenen Vorschläge.')
        ->assertSee('Streik legt Nahverkehr in Rom lahm');

    // Ein zweites Mal laesst sich derselbe Vorschlag nicht umwandeln.
    Livewire::test(Index::class)->set('tab', 'today')->call('createDraftFromSuggestion', $suggestion->id);
    expect(CustomEvent::count())->toBe(1);

    // Das Formular des Entwurfs laesst sich oeffnen.
    $this->get(route('adminv2.events.edit', $event))->assertOk()->assertSee('Streik legt Nahverkehr in Rom lahm');
});

it('sucht auf Wunsch gezielt mit den Filtern der Liste – die allgemeinen Vorschlaege bleiben erhalten', function () {
    Bus::fake();

    // Damit Italien im Laender-Filter der Liste waehlbar ist, braucht es ein Ereignis dort.
    $existing = CustomEvent::create([
        'title' => 'Altes Ereignis', 'popup_content' => 'Text', 'event_type' => 'other', 'priority' => 'low',
        'start_date' => now()->subDays(30), 'end_date' => now()->subDays(20), 'is_active' => true, 'archived' => false, 'review_status' => 'approved',
    ]);
    $existing->countries()->attach($this->italy->id, ['use_default_coordinates' => true]);

    // Zwei allgemeine Vorschlaege von heute.
    AiEventSuggestion::create(['title' => 'Streik legt Nahverkehr in Rom lahm', 'priority' => 'medium', 'country_codes' => ['IT'], 'event_type_codes' => ['strike'], 'start_date' => '2026-10-03', 'end_date' => '2026-10-04']);
    AiEventSuggestion::create(['title' => 'Waldbrände auf Rhodos', 'priority' => 'high', 'country_codes' => ['GR'], 'event_type_codes' => ['environment'], 'start_date' => '2026-10-01']);

    $strike = EventType::firstWhere('code', 'strike');

    $list = Livewire::test(Index::class)
        ->set('tab', 'today')
        // Ohne Filter: nur die allgemeine Suche, alle Vorschlaege sichtbar.
        ->assertDontSee('KI mit diesen Filtern suchen lassen')
        ->assertSee('Waldbrände auf Rhodos')
        ->set('countryIds', [(string) $this->italy->id])
        ->set('types', [(string) $strike->id])
        ->set('periodFrom', '2026-10-01')
        ->set('periodTo', '2026-10-10')
        // Mit Filtern: die Anzeige folgt ihnen, nichts geht verloren.
        ->assertSee('KI mit diesen Filtern suchen lassen')
        ->assertSee('Länder: Italien (IT)')
        ->assertSee('Streik legt Nahverkehr in Rom lahm')
        ->assertDontSee('Waldbrände auf Rhodos')
        ->assertSee('Alle 2 Vorschläge zeigen')
        ->set('showAllSuggestions', true)
        ->assertSee('Waldbrände auf Rhodos')
        ->set('showAllSuggestions', false);

    expect($list->instance()->aiSuggestions)->toHaveCount(1)
        ->and($list->instance()->allAiSuggestions)->toHaveCount(2);

    // Gezielte Suche: der Lauf merkt sich die Eingrenzung.
    $list->call('startFilteredAiSearch')->assertSee('Die KI sucht gezielt nach aktuellen Ereignissen');

    $search = AiEventSearch::first();

    expect($search->isTargeted())->toBeTrue()
        ->and($search->filters['countries'])->toBe(['IT'])
        ->and($search->filters['types'])->toBe(['strike'])
        ->and($search->filters['from'])->toBe('2026-10-01')
        ->and($search->filterSummary())->toBe('Länder: Italien (IT) · Event-Typen: Streik · Zeitraum: 01.10.2026 bis 10.10.2026');

    Bus::assertDispatchedAfterResponse(RunAiEventSearch::class);

    // Der Auftrag an die KI enthaelt die Eingrenzung.
    $prompt = app(AiEventSearchService::class)->buildPrompt(true, $search->filters);

    expect($prompt)->toContain('EINGRENZUNG für diese Suche')
        ->toContain('- Länder: Italien (IT)')
        ->toContain('- Event-Typen: Streik')
        ->toContain('- Zeitraum: 01.10.2026 bis 10.10.2026')
        ->and(app(AiEventSearchService::class)->buildPrompt(true))->not->toContain('EINGRENZUNG');

    // Das Ergebnis der gezielten Suche kommt zu den vorhandenen Vorschlaegen dazu.
    fakeAiSearch([aiEvent(['title' => 'Bahnstreik in Mailand', 'location' => 'Mailand', 'start_date' => '2026-10-06', 'end_date' => '2026-10-06'])]);
    app(AiEventSearchService::class)->run($search);

    expect(AiEventSuggestion::query()->open()->count())->toBe(3);

    Livewire::test(Index::class)
        ->set('tab', 'today')
        ->assertSee('gezielt – Länder: Italien (IT)')
        ->assertSee('Bahnstreik in Mailand')
        ->assertSee('Waldbrände auf Rhodos')
        // Eine allgemeine Suche laesst sich weiterhin starten.
        ->call('startAiSearch');

    expect(AiEventSearch::latest('id')->first()->isTargeted())->toBeFalse();
});

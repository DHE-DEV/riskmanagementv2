<?php

use App\Jobs\RunAiEventSearch;
use App\Livewire\AdminV2\Events\Index;
use App\Livewire\AdminV2\System\Ai;
use App\Livewire\AdminV2\System\AiSearchEditor;
use App\Models\AiEventSearch;
use App\Models\AiEventSearchProfile;
use App\Models\AiEventSearchPrompt;
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

it('verwaltet unter System > KI die Vorlagen fuer den Auftrag an die KI – eine ist der Standard', function () {
    $this->get(route('adminv2.system.ai'))->assertOk()->assertSee('KI Vorlagen')->assertDontSee('Aktuelle Ereignisse suchen');

    // Die mitgelieferte Vorlage ist der Standard.
    $standard = AiEventSearchPrompt::default();

    expect($standard->name)->toBe('Standard')
        ->and($standard->prompt)->toBe(AiSettings::DEFAULT_EVENT_SEARCH_PROMPT)
        ->and(AiSettings::eventSearchPrompt())->toBe(AiSettings::DEFAULT_EVENT_SEARCH_PROMPT);

    $page = Livewire::test(Ai::class)
        ->assertSee('Standard')
        ->assertSee('gilt für alle Suchen ohne eigene Vorlage')
        ->call('createPrompt')
        ->call('savePrompt')
        ->assertHasErrors(['promptName', 'promptText'])
        ->set('promptName', 'Standard')
        ->set('promptText', 'Suche nach Streiks im europäischen Bahnverkehr der kommenden Woche.')
        ->call('savePrompt')
        ->assertHasErrors('promptName')
        ->set('promptName', 'Bahnstreiks')
        ->call('savePrompt')
        ->assertHasNoErrors()
        ->assertSee('Bahnstreiks')
        ->assertSee('von keiner Suche gewählt');

    $strikes = AiEventSearchPrompt::firstWhere('name', 'Bahnstreiks');

    expect($strikes->is_default)->toBeFalse()
        ->and($strikes->created_by)->toBe($this->admin->id)
        ->and(AiEventSearchPrompt::count())->toBe(2);

    // Bearbeiten und zum Standard machen: es gibt immer genau einen.
    $page->call('editPrompt', $strikes->id)
        ->assertSet('promptName', 'Bahnstreiks')
        ->set('promptText', 'Suche nach angekündigten Streiks im Bahnverkehr in Europa.')
        ->call('savePrompt')
        ->call('makeDefaultPrompt', $strikes->id);

    expect(AiEventSearchPrompt::where('is_default', true)->pluck('name')->all())->toBe(['Bahnstreiks'])
        ->and(AiSettings::eventSearchPrompt())->toBe('Suche nach angekündigten Streiks im Bahnverkehr in Europa.')
        ->and(app(AiEventSearchService::class)->buildPrompt(true))->toContain('Suche nach angekündigten Streiks im Bahnverkehr in Europa.');

    // Die Standard-Vorlage laesst sich nicht loeschen, eine andere schon.
    $page->call('deletePrompt', $strikes->id);
    expect(AiEventSearchPrompt::count())->toBe(2);

    $page->call('deletePrompt', $standard->id);
    expect(AiEventSearchPrompt::pluck('name')->all())->toBe(['Bahnstreiks']);

    // Der mitgelieferte Auftrag laesst sich als Ausgangspunkt einfuegen.
    $page->call('createPrompt')->call('fillPromptWithBuiltIn')->assertSet('promptText', AiSettings::DEFAULT_EVENT_SEARCH_PROMPT);
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
        ->assertSeeHtml('<i class="fas fa-bolt text-[0.65rem] opacity-60" aria-hidden="true"></i>')
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

    // Eine hinterlegte Suche aus der Liste starten
    $general = AiEventSearchProfile::firstWhere('name', 'Allgemeine Suche');

    $list->assertSee('Suche ausführen')->call('runAiProfile', $general->id);
    Bus::assertDispatchedAfterResponse(RunAiEventSearch::class);
    expect(AiEventSearch::latest('id')->first()->profile_id)->toBe($general->id);

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

it('zeigt die Vorschlaege passend zu den Filtern der Liste und hinterlegt die Filter als Suche', function () {
    // Damit Italien im Laender-Filter der Liste waehlbar ist, braucht es ein Ereignis dort.
    $existing = CustomEvent::create([
        'title' => 'Altes Ereignis', 'popup_content' => 'Text', 'event_type' => 'other', 'priority' => 'low',
        'start_date' => now()->subDays(30), 'end_date' => now()->subDays(20), 'is_active' => true, 'archived' => false, 'review_status' => 'approved',
    ]);
    $existing->countries()->attach($this->italy->id, ['use_default_coordinates' => true]);

    AiEventSuggestion::create(['title' => 'Streik legt Nahverkehr in Rom lahm', 'priority' => 'medium', 'country_codes' => ['IT'], 'event_type_codes' => ['strike'], 'start_date' => '2026-10-03', 'end_date' => '2026-10-04']);
    AiEventSuggestion::create(['title' => 'Waldbrände auf Rhodos', 'priority' => 'high', 'country_codes' => ['GR'], 'event_type_codes' => ['environment'], 'start_date' => '2026-10-01']);

    $strike = EventType::firstWhere('code', 'strike');

    $list = Livewire::test(Index::class)
        ->set('tab', 'today')
        // Ohne Filter: alle Vorschlaege, kein Hinweis auf Filter.
        ->assertDontSee('Filter als Suche hinterlegen')
        ->assertSee('Waldbrände auf Rhodos')
        ->set('countryIds', [(string) $this->italy->id])
        ->set('types', [(string) $strike->id])
        ->set('priorities', ['medium'])
        ->set('search', 'Streik')
        // Mit Filtern: die Anzeige folgt ihnen, nichts geht verloren.
        ->assertSee('Filter als Suche hinterlegen')
        ->assertSee('Länder: Italien (IT)')
        ->assertSee('Streik legt Nahverkehr in Rom lahm')
        ->assertDontSee('Waldbrände auf Rhodos')
        ->assertSee('Alle 2 Vorschläge zeigen')
        ->set('showAllSuggestions', true)
        ->assertSee('Waldbrände auf Rhodos');

    expect($list->set('showAllSuggestions', false)->instance()->aiSuggestions)->toHaveCount(1)
        ->and($list->instance()->allAiSuggestions)->toHaveCount(2);

    // Gesucht wird nur ueber hinterlegte Suchen: die Filter lassen sich als neue Suche uebernehmen.
    $url = $list->instance()->saveFiltersAsSearchUrl;

    expect($url)->toContain('/adminv2/system/ai/searches/create')->toContain('countries=IT')->toContain('types=strike');

    parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

    Livewire::withQueryParams($query)
        ->test(AiSearchEditor::class)
        ->assertSet('countries', ['IT'])
        ->assertSet('types', ['strike'])
        ->assertSet('priorities', ['medium'])
        ->assertSet('keyword', 'Streik');
});

it('laesst mehrere Suchen mit Vorlage, Filtern und Zeitplan hinterlegen und fuehrt sie automatisch aus', function () {
    Bus::fake();

    $template = AiEventSearchPrompt::create(['name' => 'Streiks', 'prompt' => 'Suche nach angekündigten Streiks im Bahn- und Flugverkehr.']);

    $this->get(route('adminv2.system.ai.searches.create'))->assertOk()->assertSee('Neue Suche')->assertSee('KI-Vorlage');

    Livewire::withQueryParams([])
        ->test(AiSearchEditor::class)
        ->call('save')
        ->assertHasErrors('name')
        ->set('name', 'Streiks in Italien')
        ->set('promptId', (string) $template->id)
        ->assertSee('Suche nach angekündigten Streiks im Bahn- und Flugverkehr.')
        ->set('countries', ['IT'])
        ->set('types', ['strike'])
        ->set('priorities', ['high', 'medium'])
        ->set('daysAhead', '7')
        ->set('keyword', 'Bahn')
        ->set('weekdays', ['1', '2', '3', '4', '5'])
        ->call('addTime')
        ->set('times', ['13:00', '07:00', ''])
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('adminv2.system.ai.searches.edit', AiEventSearchProfile::firstWhere('name', 'Streiks in Italien')));

    $strikes = AiEventSearchProfile::firstWhere('name', 'Streiks in Italien');

    // Freitag, 02.10.2026, 10:00 Uhr: der naechste Lauf ist heute um 13:00.
    expect($strikes->sortedTimes())->toBe(['07:00', '13:00'])
        ->and($strikes->prompt_id)->toBe($template->id)
        ->and($strikes->next_run_at->format('Y-m-d H:i'))->toBe('2026-10-02 13:00')
        ->and($strikes->created_by)->toBe($this->admin->id);

    // Die Liste unter System > KI zeigt die Suchen und verlinkt ihre Seiten.
    Livewire::test(Ai::class)
        ->assertSee('Hinterlegte Suchen')
        ->assertSee('Streiks in Italien')
        ->assertSee('Vorlage: Streiks')
        ->assertSee('Montag bis Freitag um 07:00 und 13:00 Uhr')
        ->assertSee('nächster Lauf 02.10.2026 13:00')
        ->assertSee('Länder: Italien (IT) · Event-Typen: Streik · Priorität: Mittel, Hoch · Zeitraum: die nächsten 7 Tage ab dem Tag der Suche · Stichwort: Bahn')
        ->assertSee(route('adminv2.system.ai.searches.edit', $strikes))
        // Die mitgelieferte allgemeine Suche nutzt die Standard-Vorlage und laeuft nur von Hand.
        ->assertSee('Allgemeine Suche')
        ->assertSee('Vorlage: Standard');

    // Die eigene Seite der Suche: die Werte stehen im Formular.
    $this->get(route('adminv2.system.ai.searches.edit', $strikes))->assertOk()->assertSee('Suchen und Ergebnisse')->assertSee('Diese Suche ist noch nicht gelaufen.');

    Livewire::test(AiSearchEditor::class, ['profile' => $strikes->id])
        ->assertSet('countries', ['IT'])
        ->assertSet('times', ['07:00', '13:00'])
        ->assertSet('daysAhead', '7')
        ->assertSet('promptId', (string) $template->id);

    // Vor dem Zeitpunkt passiert nichts.
    $this->artisan('ai:run-event-searches')->assertSuccessful();
    expect(AiEventSearch::count())->toBe(0);

    // 13:02 Uhr: die Streik-Suche laeuft – mit ihrer Vorlage und ihren Filtern.
    Carbon::setTestNow('2026-10-02 13:02:00');
    fakeAiSearch([aiEvent(['title' => 'Bahnstreik in Mailand', 'location' => 'Mailand'])]);

    $this->artisan('ai:run-event-searches')->assertSuccessful();
    $this->artisan('ai:run-event-searches')->assertSuccessful();

    $search = AiEventSearch::first();

    expect(AiEventSearch::count())->toBe(1)
        ->and($search->profile_id)->toBe($strikes->id)
        ->and($search->status)->toBe(AiEventSearch::STATUS_DONE)
        ->and($search->started_by)->toBeNull()
        ->and($search->prompt_name)->toBe('Streiks')
        ->and($search->prompt)->toBe('Suche nach angekündigten Streiks im Bahn- und Flugverkehr.')
        ->and($search->filters['countries'])->toBe(['IT'])
        ->and($search->filters['from'])->toBe('2026-10-02')
        ->and($search->filters['to'])->toBe('2026-10-09')
        ->and(AiEventSuggestion::count())->toBe(1)
        // Der naechste Lauf ist am Montag um 07:00 (Samstag und Sonntag sind nicht gewaehlt).
        ->and($strikes->fresh()->next_run_at->format('Y-m-d H:i'))->toBe('2026-10-05 07:00')
        ->and($strikes->fresh()->last_run_at->format('Y-m-d H:i'))->toBe('2026-10-02 13:02');

    Http::assertSent(fn (Request $request) => $request->url() === 'https://api.openai.com/v1/responses'
        && str_contains($request['input'], 'Suche nach angekündigten Streiks im Bahn- und Flugverkehr.')
        && ! str_contains($request['input'], 'letzten 48 Stunden')
        && str_contains($request['input'], '- Länder: Italien (IT)')
        && str_contains($request['input'], '- Zeitraum: 02.10.2026 bis 09.10.2026')
        && str_contains($request['input'], '- Stichwort: Bahn'));

    // Der Vorschlag zeigt, aus welcher Suche er stammt.
    Livewire::test(Index::class)->set('tab', 'today')->assertSee('Suche „Streiks in Italien“');

    // Ohne eigene Vorlage laeuft eine Suche mit dem Standard – auch nachdem die gewaehlte Vorlage geloescht wurde.
    $general = AiEventSearchProfile::firstWhere('name', 'Allgemeine Suche');

    expect($general->effectivePrompt()->name)->toBe('Standard');

    $template->delete();

    expect($strikes->fresh()->prompt_id)->toBeNull()
        ->and($strikes->fresh()->effectivePrompt()->name)->toBe('Standard');

    // Pausieren, fortsetzen, von Hand ausfuehren, loeschen.
    $page = Livewire::test(Ai::class)
        ->assertSee('1 neu von 1')
        ->call('toggleProfile', $strikes->id);

    expect($strikes->fresh()->is_active)->toBeFalse()->and($strikes->fresh()->next_run_at)->toBeNull();

    $page->call('toggleProfile', $strikes->id)->call('runAiProfile', $general->id)->assertSee('Die KI sucht nach aktuellen Ereignissen');

    $manual = AiEventSearch::latest('id')->first();

    expect($manual->profile_id)->toBe($general->id)
        ->and($manual->started_by)->toBe($this->admin->id)
        ->and($manual->prompt_name)->toBe('Standard')
        ->and($manual->isTargeted())->toBeFalse();

    Bus::assertDispatchedAfterResponse(RunAiEventSearch::class, fn (RunAiEventSearch $job) => $job->searchId === $manual->id);

    // Waehrend eine Suche laeuft, startet keine zweite.
    $page->call('runAiProfile', $strikes->id);
    expect(AiEventSearch::count())->toBe(2);

    $page->call('deleteProfile', $strikes->id);

    expect(AiEventSearchProfile::pluck('name')->all())->toBe(['Allgemeine Suche'])
        ->and(AiEventSuggestion::count())->toBe(1)
        ->and($search->fresh()->profile_id)->toBeNull();

    // Eine ungueltige Uhrzeit wird abgelehnt.
    Livewire::test(AiSearchEditor::class)->set('name', 'Kaputt')->set('times', ['25:99'])->call('save')->assertHasErrors('times.0');
});

it('zeigt auf der Seite einer Suche ihre Laeufe mit Ergebnissen und den ins Ereignis uebernommenen Text', function () {
    $profile = AiEventSearchProfile::create(['name' => 'Streiks in Italien', 'country_codes' => ['IT']]);
    $service = app(AiEventSearchService::class);

    fakeAiSearch([aiEvent(), aiEvent(['title' => 'Bahnstreik in Mailand', 'location' => 'Mailand', 'summary' => 'In Mailand fahren keine Züge.', 'sources' => []])]);
    $first = $service->run($service->createSearchFor($profile, $this->admin->id));

    Carbon::setTestNow('2026-10-02 15:00:00');
    fakeAiSearch([aiEvent(['title' => 'Fluglotsenstreik in Neapel', 'location' => 'Neapel', 'sources' => []])]);
    $second = $service->run($service->createSearchFor($profile));

    $rome = AiEventSuggestion::firstWhere('title', 'Streik legt Nahverkehr in Rom lahm');
    $milan = AiEventSuggestion::firstWhere('title', 'Bahnstreik in Mailand');

    $page = Livewire::test(AiSearchEditor::class, ['profile' => $profile->id])
        ->assertSee('2 Läufe – der letzte zuerst')
        // Der letzte Lauf steht oben.
        ->assertSeeInOrder(['02.10.2026 15:00', 'Fluglotsenstreik in Neapel', '02.10.2026 10:00', 'Streik legt Nahverkehr in Rom lahm'])
        ->assertSee('automatisch')
        ->assertSee('von Anna')
        ->assertSee('2 gefunden, 2 neu')
        ->assertSee('Vorlage „Standard“');

    // Verwerfen direkt auf der Seite
    $page->call('dismissSuggestion', $milan->id)->assertSee('Verworfen')->assertSee('Wieder vorschlagen');

    // Als Entwurf anlegen und den Text im Ereignis aendern.
    $event = $service->createDraft($rome, $this->admin->id);

    Livewire::test(AiSearchEditor::class, ['profile' => $profile->id])
        ->assertSee('Übernommen ins Passolution Ereignis')
        ->assertSee('Titel und Text wurden unverändert aus dem Vorschlag übernommen.')
        ->assertSee('Zum Ereignis');

    $event->fill([
        'title_translations' => ['de' => 'Rom: Nahverkehr bestreikt'],
        'popup_content_translations' => ['de' => '<p>Busse und Bahnen fahren am 3. und 4. Oktober nur eingeschränkt.</p>'],
    ])->save();

    Livewire::test(AiSearchEditor::class, ['profile' => $profile->id])
        // Der Stand im Ereignis …
        ->assertSee('Rom: Nahverkehr bestreikt')
        ->assertSee('Busse und Bahnen fahren am 3. und 4. Oktober nur eingeschränkt.')
        // … neben dem Vorschlag der KI.
        ->assertSee('Streik legt Nahverkehr in Rom lahm')
        ->assertSee('Titel oder Text wurden im Ereignis angepasst');

    // Von der Seite aus ausfuehren und loeschen
    Bus::fake();
    Livewire::test(AiSearchEditor::class, ['profile' => $profile->id])
        ->call('runNow')
        ->assertSee('Die KI sucht gezielt nach aktuellen Ereignissen')
        ->call('delete')
        ->assertRedirect(route('adminv2.system.ai'));

    Bus::assertDispatchedAfterResponse(RunAiEventSearch::class);
    expect(AiEventSearchProfile::whereKey($profile->id)->exists())->toBeFalse();
});

it('laesst fuer eine hinterlegte Suche alle Laender waehlen und einzelne ausnehmen', function () {
    Country::factory()->create(['iso_code' => 'RU', 'iso3_code' => 'RUS', 'name_translations' => ['de' => 'Russland', 'en' => 'Russia']]);

    Livewire::withQueryParams([])
        ->test(AiSearchEditor::class)
        ->set('name', 'Weltweit ohne Russland')
        ->set('times', ['08:00'])
        // Alle auswaehlen, dann eines abwaehlen.
        ->call('selectAllCountries')
        ->assertSet('countries', ['GR', 'IT', 'RU'])
        ->assertSee('alle 3 gewählt')
        ->set('countries', ['GR', 'IT'])
        ->assertSee('2 von 3 gewählt – 1 ausgenommen')
        // Der Zeitraum ist eine Auswahl mit Beispiel statt einer Zahl.
        ->assertSee('Keine zeitliche Eingrenzung')
        ->set('daysAhead', '14')
        ->assertSee('zwischen dem 02.10.2026 und dem 16.10.2026')
        ->call('save')
        ->assertHasNoErrors();

    $profile = AiEventSearchProfile::firstWhere('name', 'Weltweit ohne Russland');

    expect($profile->filterSummary())->toContain('Länder: alle außer Russland (RU)');

    // Der Auftrag an die KI nennt die Ausnahme statt einer langen Laenderliste.
    $prompt = app(AiEventSearchService::class)->buildPrompt(true, $profile->filtersForRun());

    expect($prompt)->toContain('- Länder: alle außer Russland (RU)')
        ->toContain('- Zeitraum: 02.10.2026 bis 16.10.2026');

    // Sind alle Laender gewaehlt, ist das keine Eingrenzung.
    Livewire::test(AiSearchEditor::class, ['profile' => $profile->id])->call('selectAllCountries')->set('daysAhead', '')->call('save')->assertHasNoErrors();

    expect($profile->fresh()->filtersForRun())->toBeNull();

    // Alle abwaehlen
    Livewire::test(AiSearchEditor::class, ['profile' => $profile->id])->call('clearCountries')->assertSet('countries', [])->assertSee('keine Eingrenzung');
});

it('zeigt unter "KI Suchergebnisse" die Suchen und alles, was sie gefunden haben', function () {
    fakeAiSearch([aiEvent(), aiEvent(['title' => 'Waldbrände auf Rhodos', 'countries' => ['GR'], 'location' => 'Rhodos', 'event_types' => ['environment'], 'priority' => 'high', 'sources' => []])]);
    $first = runAiSearch();

    Carbon::setTestNow('2026-10-02 12:00:00');
    fakeAiSearch([aiEvent(['title' => 'Bahnstreik in Mailand', 'location' => 'Mailand', 'sources' => []])]);
    $profile = AiEventSearchProfile::create(['name' => 'Streiks in Italien', 'country_codes' => ['IT'], 'times' => ['12:00']]);
    $second = app(AiEventSearchService::class)->run(app(AiEventSearchService::class)->createSearchFor($profile));

    $rome = AiEventSuggestion::firstWhere('title', 'Streik legt Nahverkehr in Rom lahm');
    $rhodes = AiEventSuggestion::firstWhere('title', 'Waldbrände auf Rhodos');

    $this->get(route('adminv2.events.ai-results'))->assertOk()->assertSee('KI Suchergebnisse');

    $titles = fn ($page) => collect($page->viewData('suggestions')->items())->pluck('title')->all();

    $page = Livewire::test(\App\Livewire\AdminV2\Events\AiResults::class)
        // Die Suchlaeufe mit Herkunft, Eingrenzung und Ergebnis
        ->assertSee('Allgemeine Suche')
        ->assertSee('Streiks in Italien')
        ->assertSee('automatisch')
        ->assertSee('Länder: Italien (IT)')
        ->assertSee('2 gefunden, 2 neu')
        ->assertSee('1 gefunden, 1 neu')
        // Offene Ergebnisse, neueste zuerst
        ->assertSee('Suche „Streiks in Italien“');

    expect($titles($page))->toBe(['Bahnstreik in Mailand', 'Waldbrände auf Rhodos', 'Streik legt Nahverkehr in Rom lahm'])
        ->and($page->instance()->tabCounts)->toBe(['new' => 3, 'converted' => 0, 'dismissed' => 0, 'all' => 3]);

    // Ergebnisse eines Laufs
    expect($titles($page->call('showRun', $first->id)))->toBe(['Waldbrände auf Rhodos', 'Streik legt Nahverkehr in Rom lahm']);
    $page->assertSee('Auswahl aufheben')->call('showRun', $first->id)->assertSet('searchId', '');

    // Suche im Text
    expect($titles($page->set('search', 'rhodos')))->toBe(['Waldbrände auf Rhodos']);
    $page->set('search', '');

    // Verwerfen und wieder vorschlagen
    $page->call('dismissSuggestion', $rhodes->id);

    expect($page->instance()->tabCounts)->toBe(['new' => 2, 'converted' => 0, 'dismissed' => 1, 'all' => 3])
        ->and($titles($page->set('status', 'dismissed')))->toBe(['Waldbrände auf Rhodos']);

    $page->assertSee('Wieder vorschlagen')->call('restoreSuggestion', $rhodes->id);

    expect($rhodes->fresh()->status)->toBe(AiEventSuggestion::STATUS_NEW);

    // Als Entwurf anlegen: das Ergebnis wandert in "Als Entwurf angelegt" und verlinkt das Ereignis.
    $page->set('status', 'new')->call('createDraftFromSuggestion', $rome->id);

    $event = CustomEvent::first();

    $page->assertRedirect(route('adminv2.events.edit', $event));

    Livewire::test(\App\Livewire\AdminV2\Events\AiResults::class)
        ->set('status', 'converted')
        ->assertSee('Streik legt Nahverkehr in Rom lahm')
        ->assertSee('Zum Ereignis')
        ->assertSee(route('adminv2.events.edit', $event));

    // Eine hinterlegte Suche von hier starten
    Bus::fake();
    Livewire::test(\App\Livewire\AdminV2\Events\AiResults::class)
        ->assertSee('Suche ausführen')
        ->call('runAiProfile', $profile->id)
        ->assertSee('Die KI sucht gezielt nach aktuellen Ereignissen');
    Bus::assertDispatchedAfterResponse(RunAiEventSearch::class);
});

it('benachrichtigt die bei einer hinterlegten Suche eingetragenen Benutzer und Teams mit Link zum Ergebnis', function () {
    \Illuminate\Support\Facades\Mail::fake();

    $user = fn (string $name, bool $active = true) => User::factory()->create(['name' => $name, 'is_admin' => true, 'is_active' => $active]);

    $dennis = $user('Dennis');
    $carla = $user('Carla');
    $eva = $user('Eva');
    $gone = $user('Weg', false);

    $redaktion = \App\Models\AdminTeam::create(['name' => 'Redaktion']);
    $redaktion->users()->sync([$carla->id, $dennis->id]);
    $support = \App\Models\AdminTeam::create(['name' => 'Support', 'email' => 'support@example.com', 'notify_mode' => \App\Models\AdminTeam::NOTIFY_TEAM_EMAIL]);
    $support->users()->sync([$eva->id]);

    // Im Formular: Benutzer und Teams waehlen.
    Livewire::withQueryParams([])
        ->test(AiSearchEditor::class)
        ->set('name', 'Streiks in Italien')
        ->set('countries', ['IT'])
        ->set('times', ['12:00'])
        ->set('notifyUsers', [(string) $dennis->id, (string) $gone->id, '999999'])
        ->call('save')
        ->assertHasErrors('notifyUsers.1')
        ->set('notifyUsers', [(string) $dennis->id])
        ->set('notifyTeams', [(string) $redaktion->id, (string) $support->id])
        ->call('save')
        ->assertHasNoErrors();

    $profile = AiEventSearchProfile::firstWhere('name', 'Streiks in Italien');

    Livewire::test(Ai::class)->assertSee('E-Mail an Dennis, Team Redaktion, Team Support – bei neuen Vorschlägen');

    // Dennis steht selbst und ueber die Redaktion drin – er bekommt nur eine Mail.
    expect($profile->notificationEmails())->toEqualCanonicalizing([$dennis->email, $carla->email, 'support@example.com']);

    Livewire::test(AiSearchEditor::class, ['profile' => $profile->id])
        ->assertSet('notifyUsers', [(string) $dennis->id])
        ->assertSet('notifyTeams', [(string) $redaktion->id, (string) $support->id]);

    // Lauf mit neuem Ergebnis: Mail an alle, mit Link direkt zu diesem Lauf.
    Carbon::setTestNow('2026-10-02 12:01:00');
    fakeAiSearch([aiEvent(['title' => 'Bahnstreik in Mailand', 'location' => 'Mailand'])]);
    $this->artisan('ai:run-event-searches')->assertSuccessful();

    $search = AiEventSearch::first();
    $url = route('adminv2.events.ai-results', ['run' => $search->id, 'status' => 'all']);

    \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\AiEventSearchResultMail::class, 3);
    \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\AiEventSearchResultMail::class, fn ($mail) => $mail->hasTo('support@example.com'));
    \Illuminate\Support\Facades\Mail::assertNotSent(\App\Mail\AiEventSearchResultMail::class, fn ($mail) => $mail->hasTo($eva->email));

    $mail = new \App\Mail\AiEventSearchResultMail($search->fresh());

    expect($mail->envelope()->subject)->toBe('KI-Suche „Streiks in Italien“: 1 neuer Vorschlag')
        ->and($mail->render())->toContain('Bahnstreik in Mailand')
        ->toContain('Suchergebnis öffnen')
        ->toContain(e($url))
        ->and($search->fresh()->notified_at)->not->toBeNull();

    // Der Link fuehrt auf die Ergebnisse genau dieses Laufs.
    $this->get($url)->assertOk()->assertSee('Bahnstreik in Mailand')->assertSee('Auswahl aufheben');

    // Kein zweites Mal fuer denselben Lauf.
    expect(app(AiEventSearchService::class)->notifyRecipients($search->fresh()))->toBe(0);

    // Lauf ohne Neues: keine Mail – ausser es ist so eingestellt.
    $run = fn () => app(AiEventSearchService::class)->run(app(AiEventSearchService::class)->createSearchFor($profile->fresh()));

    fakeAiSearch([aiEvent(['title' => 'Bahnstreik in Mailand', 'location' => 'Mailand'])]);
    $run();
    \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\AiEventSearchResultMail::class, 3);

    $profile->update(['notify_when_empty' => true]);
    $run();
    \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\AiEventSearchResultMail::class, 6);

    // Ein fehlgeschlagener Lauf wird immer gemeldet.
    $profile->update(['notify_when_empty' => false]);
    $GLOBALS['aiSearchResponse'] = fn () => Http::response(['error' => ['message' => 'Quota exceeded']], 429);
    $failed = $run();

    \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\AiEventSearchResultMail::class, 9);
    expect((new \App\Mail\AiEventSearchResultMail($failed->fresh()))->envelope()->subject)->toBe('KI-Suche „Streiks in Italien“: fehlgeschlagen');

    // Eine Suche ohne Profil (von Hand, allgemein) verschickt nichts.
    fakeAiSearch([aiEvent(['title' => 'Etwas ganz anderes', 'countries' => ['GR'], 'location' => 'Athen', 'sources' => []])]);
    runAiSearch();
    \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\AiEventSearchResultMail::class, 9);
});

it('begrenzt die Zahl der Ergebnisse je hinterlegter Suche', function () {
    $service = app(AiEventSearchService::class);

    // Standard: 15
    expect(AiSettings::eventSearchMaxResults())->toBe(15)
        ->and($service->buildPrompt(true))->toContain('Liefere höchstens 15 Ereignisse');

    // Je hinterlegter Suche eine eigene Zahl
    Livewire::withQueryParams([])
        ->test(AiSearchEditor::class)
        ->set('name', 'Nur das Wichtigste')
        ->set('maxResults', '99')
        ->call('save')
        ->assertHasErrors('maxResults')
        ->set('maxResults', '2')
        ->call('save')
        ->assertHasNoErrors();

    $profile = AiEventSearchProfile::firstWhere('name', 'Nur das Wichtigste');

    Livewire::test(Ai::class)->assertSee('höchstens 2 Ergebnisse');

    // Liefert die KI trotzdem mehr, zaehlen nur die ersten.
    fakeAiSearch([
        aiEvent(['title' => 'Erstes Thema', 'location' => 'Rom', 'sources' => []]),
        aiEvent(['title' => 'Zweites Thema', 'location' => 'Mailand', 'event_types' => ['environment'], 'sources' => []]),
        aiEvent(['title' => 'Drittes Thema', 'location' => 'Neapel', 'event_types' => ['travel'], 'sources' => []]),
    ]);

    $search = $service->run($service->createSearchFor($profile));

    expect($search->max_results)->toBe(2)
        ->and($search->found_count)->toBe(2)
        ->and(AiEventSuggestion::pluck('title')->all())->toBe(['Erstes Thema', 'Zweites Thema']);

    Http::assertSent(fn (Request $request) => $request->url() === 'https://api.openai.com/v1/responses'
        && str_contains($request['input'], 'Liefere höchstens 2 Ereignisse'));

    // Ohne eigene Zahl gilt der Standard.
    $profile->update(['max_results' => null]);
    AiEventSuggestion::query()->delete();

    expect($service->run($service->createSearchFor($profile->fresh()))->found_count)->toBe(3);
});

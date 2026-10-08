<?php

use App\Livewire\AdminV2\Events\Editor;
use App\Livewire\AdminV2\Events\Infosystem;
use App\Models\Country;
use App\Models\CustomEvent;
use App\Models\EventType;
use App\Models\InfosystemEntry;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

/**
 * Passolution Infosystem im Admin-Bereich: Eintraege aus der Passolution-API
 * abrufen, in der Datenbank ablegen und daraus Ereignisse anlegen.
 */
function infosystemAdmin(): User
{
    return User::factory()->create(['is_admin' => true, 'is_active' => true]);
}

/**
 * Ein Eintrag, wie ihn die API liefert.
 */
function infosystemApiEntry(int $id, string $header, array $overrides = []): array
{
    return array_merge([
        'id' => $id,
        'position' => 1,
        'appearance' => 0,
        'country' => 'IT',
        'country_name' => ['de' => 'Italien', 'en' => 'Italy'],
        'lang' => 'de',
        'language_content' => 'German',
        'language_code' => 'de',
        'tagtype' => 2,
        'tagtext' => 'Reiseverkehr',
        'tagdate' => '2026-10-07',
        'header' => $header,
        'content' => "Der Nahverkehr steht still.\n\nBetroffen sind Bus und Bahn.",
        'archive' => 0,
        'active' => 1,
        'categories' => [['name' => 'Reiseverkehr'], ['name' => 'Sicherheit']],
        'created_at' => '2026-10-07 08:00:00',
    ], $overrides);
}

function infosystemApiPage(array $entries, int $page, int $lastPage, int $total): array
{
    return [
        'requestid' => 'req-'.$page,
        'responsetime' => 120,
        'result' => [
            'total' => $total,
            'current_page' => $page,
            'last_page' => $lastPage,
            'data' => $entries,
        ],
    ];
}

/**
 * Zugang zur API einrichten und ihre Antworten vorgeben. Die Adresse steht
 * fest, damit der Test unabhaengig von der lokalen .env ist.
 */
function fakeInfosystemApi(mixed $responses): void
{
    config([
        'services.passolution.api_key' => 'test-key',
        'services.passolution.api_url' => 'https://api.passolution.eu/api/v2',
    ]);

    Http::preventStrayRequests();
    Http::fake($responses instanceof Closure ? $responses : ['api.passolution.eu/api/v2/infosystem/general*' => $responses]);
}

function infosystemToast(string $contains, string $variant = 'success'): Closure
{
    return fn (string $name, array $params) => str_contains($params['message'], $contains)
        && ($params['variant'] ?? 'success') === $variant;
}

it('zeigt die gespeicherten Eintraege und den Weg zum Infosystem in der Navigation', function () {
    InfosystemEntry::factory()->create(['header' => 'Italien - Streik im Nahverkehr', 'api_id' => 7001]);

    $this->actingAs(infosystemAdmin())
        ->get(route('adminv2.events.infosystem'))
        ->assertOk()
        ->assertSee('Passolution Infosystem')
        ->assertSee('Italien - Streik im Nahverkehr')
        ->assertSee('Daten synchronisieren')
        ->assertSee('Letzte 100 Einträge abrufen');
});

it('weist auf die fehlende API-Konfiguration hin und ruft dann nichts ab', function () {
    config(['services.passolution.api_key' => null]);
    Http::fake();

    $this->actingAs(infosystemAdmin());

    Livewire::test(Infosystem::class)
        ->assertSee('API-Konfiguration fehlt')
        ->call('sync')
        ->assertDispatched('adminv2-toast', infosystemToast('API-Konfiguration fehlt', 'danger'))
        ->call('syncLast100')
        ->assertDispatched('adminv2-toast', infosystemToast('API-Konfiguration fehlt', 'danger'));

    Http::assertNothingSent();
    expect(InfosystemEntry::count())->toBe(0);
});

it('ruft beim Synchronisieren die erste Seite ab und speichert die Eintraege', function () {
    fakeInfosystemApi(Http::response(infosystemApiPage([
        infosystemApiEntry(7001, 'Italien - Streik im Nahverkehr'),
        infosystemApiEntry(7002, 'Spanien - Unwetter an der Küste', ['country' => 'ES', 'country_name' => ['de' => 'Spanien', 'en' => 'Spain']]),
    ], 1, 3, 60)));

    $this->actingAs(infosystemAdmin());

    Livewire::test(Infosystem::class)
        ->call('sync')
        ->assertDispatched('adminv2-toast', infosystemToast('2 Einträge aus dem externen Infosystem abgerufen und gespeichert'))
        ->assertSee('Italien - Streik im Nahverkehr')
        ->assertSee('Spanien - Unwetter an der Küste');

    Http::assertSentCount(1);
    Http::assertSent(fn (Request $request) => $request['lang'] === 'de' && (int) $request['page'] === 1
        && $request->hasHeader('Authorization', 'Bearer test-key'));

    $entry = InfosystemEntry::where('api_id', 7001)->first();

    expect(InfosystemEntry::count())->toBe(2)
        ->and($entry->header)->toBe('Italien - Streik im Nahverkehr')
        ->and($entry->country_code)->toBe('IT')
        ->and($entry->country_names)->toBe(['de' => 'Italien', 'en' => 'Italy'])
        ->and($entry->categories)->toBe([['name' => 'Reiseverkehr'], ['name' => 'Sicherheit']])
        ->and($entry->tagdate->format('Y-m-d'))->toBe('2026-10-07')
        ->and($entry->request_id)->toBe('req-1')
        ->and($entry->is_published)->toBeFalsy();
});

it('aktualisiert beim erneuten Abruf vorhandene Eintraege statt sie zu doppeln', function () {
    $existing = InfosystemEntry::factory()->create(['api_id' => 7001, 'header' => 'Italien - Alter Titel']);

    fakeInfosystemApi(Http::response(infosystemApiPage([
        infosystemApiEntry(7001, 'Italien - Streik verlängert'),
    ], 1, 1, 1)));

    $this->actingAs(infosystemAdmin());

    Livewire::test(Infosystem::class)->call('sync');

    expect(InfosystemEntry::count())->toBe(1)
        ->and($existing->fresh()->header)->toBe('Italien - Streik verlängert');
});

it('meldet einen fehlgeschlagenen Abruf', function () {
    fakeInfosystemApi(Http::response('Server Error', 500));

    $this->actingAs(infosystemAdmin());

    Livewire::test(Infosystem::class)
        ->call('sync')
        ->assertDispatched('adminv2-toast', infosystemToast('API request failed with status: 500', 'danger'));

    expect(InfosystemEntry::count())->toBe(0);
});

it('blaettert beim Abruf der letzten 100 Eintraege durch die Seiten der API', function () {
    // Die API liefert 60 Eintraege je Seite – fuer 100 reichen zwei Seiten,
    // von der zweiten werden nur 40 gespeichert.
    fakeInfosystemApi(function (Request $request) {
        $page = (int) $request['page'];
        $entries = [];

        for ($i = 1; $i <= 60; $i++) {
            $id = ($page - 1) * 60 + $i;
            $entries[] = infosystemApiEntry(8000 + $id, 'Eintrag '.$id);
        }

        return Http::response(infosystemApiPage($entries, $page, 3, 180));
    });

    $this->actingAs(infosystemAdmin());

    Livewire::test(Infosystem::class)
        ->call('syncLast100')
        ->assertDispatched('adminv2-toast', infosystemToast('Erfolgreich 100 Einträge über 2 Seiten abgerufen und gespeichert'));

    Http::assertSentCount(2);
    expect(InfosystemEntry::count())->toBe(100);
});

it('filtert die Eintraege nach Veroeffentlichung, Sprache und Suche', function () {
    // Aeltere Eintraege haben in "is_published" noch NULL statt false.
    InfosystemEntry::factory()->create(['header' => 'Italien - Streik', 'api_id' => 7001, 'lang' => 'de', 'is_published' => null]);
    InfosystemEntry::factory()->english()->create(['header' => 'France - Strike', 'api_id' => 7002, 'is_published' => true, 'published_at' => now()]);

    $this->actingAs(infosystemAdmin());

    Livewire::test(Infosystem::class)
        ->assertSee('Italien - Streik')
        ->assertSee('France - Strike')
        ->set('published', 'yes')
        ->assertDontSee('Italien - Streik')
        ->assertSee('France - Strike')
        ->set('published', 'no')
        ->assertSee('Italien - Streik')
        ->assertDontSee('France - Strike')
        ->set('published', '')
        ->set('lang', 'de')
        ->assertSee('Italien - Streik')
        ->assertDontSee('France - Strike')
        ->set('lang', '')
        ->set('search', '7002')
        ->assertDontSee('Italien - Streik')
        ->assertSee('France - Strike')
        ->call('resetFilters')
        ->assertSet('search', '')
        ->assertSee('Italien - Streik');
});

it('legt aus einem Eintrag ein vorbelegtes Ereignis an und markiert den Eintrag als veroeffentlicht', function () {
    $travel = EventType::create(['code' => 'travel', 'name' => 'Reiseverkehr', 'icon' => 'fa-bus', 'color' => '#3B82F6', 'is_active' => true, 'sort_order' => 1]);
    $safety = EventType::create(['code' => 'safety', 'name' => 'Sicherheit', 'icon' => 'fa-shield-alt', 'color' => '#EF4444', 'is_active' => true, 'sort_order' => 2]);
    $italy = Country::factory()->create(['iso_code' => 'IT', 'iso3_code' => 'ITA', 'lat' => 41.9028, 'lng' => 12.4964]);

    $entry = InfosystemEntry::factory()->create([
        'api_id' => 7001,
        'header' => 'Italien - Streik im Nahverkehr',
        'content' => "Der Nahverkehr steht still.\n\nBetroffen sind Bus und Bahn.",
        'country_code' => 'IT',
        'country_names' => ['de' => 'Italien', 'en' => 'Italy'],
        'tagdate' => '2026-10-07',
        'categories' => [['name' => 'Reiseverkehr'], ['name' => 'Sicherheit']],
    ]);

    $this->actingAs(infosystemAdmin());

    // Die Liste verlinkt auf das vorbelegte Formular.
    Livewire::test(Infosystem::class)
        ->assertSee('Event anlegen')
        ->assertSeeHtml('href="'.route('adminv2.events.create', ['infosystem' => 7001]).'"');

    $editor = Livewire::withQueryParams(['infosystem' => 7001])
        ->test(Editor::class)
        ->assertSet('infosystemApiId', 7001)
        ->assertSet('titles.de', 'Streik im Nahverkehr')
        ->assertSet('contents.de', '<p>Der Nahverkehr steht still.</p><p>Betroffen sind Bus und Bahn.</p>')
        ->assertSet('startDate', '2026-10-07T00:00')
        ->assertSet('eventTypeIds', [(string) $travel->id, (string) $safety->id])
        ->assertSee('Aus dem Passolution Infosystem übernommen')
        ->call('save')
        ->assertHasNoErrors();

    $event = CustomEvent::query()->latest('id')->first();

    expect($event->getTitle('de'))->toBe('Streik im Nahverkehr')
        ->and($event->data_source)->toBe('passolution_infosystem')
        ->and((string) $event->data_source_id)->toBe('7001')
        ->and($event->countries()->pluck('countries.id')->all())->toBe([$italy->id])
        ->and($event->eventTypes()->pluck('event_types.id')->sort()->values()->all())->toBe(collect([$travel->id, $safety->id])->sort()->values()->all());

    $entry->refresh();

    expect($entry->is_published)->toBeTrue()
        ->and($entry->published_at)->not->toBeNull()
        ->and($entry->published_as_event_id)->toBe($event->id);

    // Ein veroeffentlichter Eintrag bietet den Weg zum Ereignis statt "Event anlegen".
    Livewire::test(Infosystem::class)
        ->assertDontSee('Event anlegen')
        ->assertSee('Ereignis öffnen');

    // Ein bereits veroeffentlichter Eintrag belegt das Formular nicht noch einmal vor.
    Livewire::withQueryParams(['infosystem' => 7001])
        ->test(Editor::class)
        ->assertSet('infosystemApiId', null)
        ->assertSet('titles.de', '');
});
